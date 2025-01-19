<?php
// Configuración de errores y memoria
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M');

// Incluir autoloader de Composer y Config
require_once 'config/config.php';
require_once 'vendor/autoload.php';


// Importar clases necesarias
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Image;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\ListItem;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;

if (!defined('FONT_LATO')) define('FONT_LATO', 'Lato');
if (!defined('FONT_LATO_LIGHT')) define('FONT_LATO_LIGHT', 'Lato Light');
if (!defined('COLOR_BLUE')) define('COLOR_BLUE', '1F4E79');
if (!defined('DEFAULT_FONT_SIZE')) define('DEFAULT_FONT_SIZE', 18);

// Configuración
$config = [
    'background_images' => [
        'portada' => 'assets/img/portada.png',
        'hoja2' => '', // Se establecerá dinámicamente
        'hoja3' => 'assets/img/hoja3.png',
        'hoja4' => 'assets/img/hoja4.png',
    ],
    'temp_directory' => sys_get_temp_dir(),
    'base_artist_path' => 'C:/xampp/htdocs/cubic/admin/assets/img/'
];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['id'])) {
        // Obtener ID del evento
        $evento_id = isset($_POST['evento_id']) ? $_POST['evento_id'] : $_GET['id'];

        // Obtener datos del evento
        $db = new DatabaseConnection();
        $evento = $db->getEventData($evento_id);

        // Establecer la ruta de la imagen del artista
        if (!empty($evento['artista_id'])) {
            $artistImagePath = $config['base_artist_path'] . $evento['artista_id'] . '/presentacion.png';

            // Verificar que la imagen existe
            if (file_exists($artistImagePath)) {
                $config['background_images']['hoja2'] = $artistImagePath;
            } else {
                // Si no existe la imagen del artista, usar una imagen por defecto
                $config['background_images']['hoja2'] = 'assets/img/hoja2.png';
                error_log("Imagen de artista no encontrada: " . $artistImagePath);
            }
        } else {
            // Si no hay artista_id, usar imagen por defecto
            $config['background_images']['hoja2'] = 'assets/img/hoja2.png';
        }

        // Preparar datos para la cotización
        $formData = [
            'encabezado' => !empty($evento['encabezado_evento'])
                ? $evento['encabezado_evento']
                : ($evento['nombres'] . ' ' . $evento['apellidos']),
            'nombres' => $evento['nombres'],
            'apellidos' => $evento['apellidos'],
            'es_encabezado_evento' => !empty($evento['encabezado_evento']),
            'ciudad' => $evento['ciudad_evento'],
            'fecha' => $evento['fecha_evento'],
            'horario' => $evento['hora_evento'],
            'evento' => $evento['nombre_evento'],
            'valor' => $evento['valor_evento'],
            'hotel' => $evento['hotel'],
            'transporte' => $evento['traslados'],
            'viaticos' => $evento['viaticos'],
            'presentacion_artista' => $evento['presentacion_artista']
        ];

        // Generar cotización
        $quoteGenerator = new QuoteGenerator($formData, $config);
        $quoteGenerator->generate();
    } else {
        throw new Exception("Método de solicitud no válido");
    }
} catch (Exception $e) {
    error_log("Error en la generación de la cotización: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

/**
 * Clase simple para logging
 */
class Logger
{
    public function log($message)
    {
        error_log("[QuoteGenerator] " . $message);
    }
}

/**
 * Clase principal para generar la cotización
 */
class QuoteGenerator
{
    private $phpWord;
    private $formData;
    private $config;
    private $fileName;
    private $logger;

    public function __construct($formData, $config)
    {
        if (empty($config) || !is_array($config)) {
            throw new Exception("La configuración es inválida o está vacía");
        }

        if (empty($config['background_images']) || !is_array($config['background_images'])) {
            throw new Exception("La configuración de imágenes es inválida o está vacía");
        }

        $this->formData = $formData;
        $this->config = $config;
        $this->logger = new Logger();

        // Validar el entorno después de establecer la configuración
        $this->validateEnvironment();

        $this->phpWord = new PhpWord();
        $this->setupDocument();
        $this->setFileName();
    }

    /**
     * Valida el entorno antes de la generación
     */
    private function validateEnvironment()
    {
        try {
            // Verificar directorio temporal
            if (!is_writable(sys_get_temp_dir())) {
                throw new Exception("El directorio temporal no tiene permisos de escritura");
            }

            // Verificar imágenes de fondo
            foreach ($this->config['background_images'] as $key => $path) {
                if (!file_exists($path)) {
                    throw new Exception("Imagen de fondo no encontrada: $path ($key)");
                }
            }

            // Verificar si PHPWord está disponible
            if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
                throw new Exception("La librería PHPWord no está disponible");
            }

            // Log de extensiones disponibles
            $this->logger->log("Extensiones PHP cargadas: " . implode(", ", get_loaded_extensions()));

            return true;
        } catch (Exception $e) {
            $this->logger->log("Error en validación del entorno: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Configura el documento inicial
     */
    private function setupDocument()
    {
        $this->logger->log("Iniciando configuración del documento");
        $this->phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));
        $this->defineStyles();
        $this->logger->log("Documento configurado correctamente");
    }

    /**
     * Define los estilos del documento
     */
    private function defineStyles()
    {
        $this->phpWord->addFontStyle('titleStyle', [
            'name' => FONT_LATO,
            'size' => 23,
            'color' => COLOR_BLUE,
            'bold' => true
        ]);

        $this->phpWord->addFontStyle('subtitleStyle', [
            'name' => FONT_LATO_LIGHT,
            'size' => 21,
            'bold' => true
        ]);

        $this->phpWord->addFontStyle('paragraphStyle', [
            'name' => FONT_LATO_LIGHT,
            'size' => DEFAULT_FONT_SIZE,
            'bold' => false
        ]);

        $this->phpWord->addFontStyle('boldParagraphStyle', [
            'name' => FONT_LATO_LIGHT,
            'size' => 20,
            'bold' => true
        ]);

        $this->phpWord->addFontStyle('normalText', [
            'name' => FONT_LATO_LIGHT,
            'size' => DEFAULT_FONT_SIZE
        ]);

        $this->phpWord->addFontStyle('finalText', [
            'name' => FONT_LATO,
            'size' => DEFAULT_FONT_SIZE,
            'italic' => true,
            'color' => COLOR_BLUE,
            'bold' => true
        ]);
    }

    /**
     * Genera el documento completo
     */
    public function generate()
    {
        try {
            $this->logger->log("Iniciando generación del documento");
            $this->validateFormData();
            $this->createCoverPage();
            $this->createSecondPage();
            $this->createThirdPage();
            $this->createFourthPage();
            $this->saveDocument();
            $this->logger->log("Documento generado exitosamente");
        } catch (Exception $e) {
            $this->logger->log("Error en generación: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida los datos del formulario
     */
    private function validateFormData()
    {
        try {
            $required_fields = [
                'encabezado' => 'Encabezado',
                'evento' => 'Nombre del evento',
                'valor' => 'Valor'
            ];

            foreach ($required_fields as $field => $label) {
                if (empty($this->formData[$field])) {
                    throw new Exception("El campo '$label' es requerido");
                }
            }

            // Validar y establecer valores por defecto para campos opcionales
            $this->formData['ciudad'] = !empty($this->formData['ciudad'])
                ? $this->formData['ciudad']
                : 'Por definir';

            $this->formData['fecha'] = !empty($this->formData['fecha'])
                ? $this->formData['fecha']
                : date('Y-m-d');

            // Manejo mejorado para el horario
            if (empty($this->formData['horario']) || $this->formData['horario'] === null) {
                $this->formData['horario'] = 'Por definir';
            } else {
                $hora = trim($this->formData['horario']);

                // Si es un objeto DateTime o string de fecha/hora válido
                if ($hora instanceof DateTime || strtotime($hora) !== false) {
                    // Convertir a formato 24 horas
                    $timestamp = $hora instanceof DateTime ? $hora->getTimestamp() : strtotime($hora);
                    $this->formData['horario'] = date('H:i', $timestamp);
                } else if (preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3])[:.]([0-5][0-9])$/', $hora)) {
                    // Si ya está en formato válido HH:mm, solo asegurarse que use ':'
                    $this->formData['horario'] = str_replace('.', ':', $hora);
                } else {
                    // Si no es un formato reconocido, mantener el valor original
                    $this->formData['horario'] = $hora;
                }
            }

            // Validar valor monetario
            if (!is_numeric($this->formData['valor'])) {
                throw new Exception("El valor debe ser numérico");
            }

            if ($this->formData['valor'] <= 0) {
                throw new Exception("El valor debe ser mayor que cero");
            }

            // Validar y sanitizar campos booleanos
            $booleanFields = ['hotel', 'transporte', 'viaticos'];
            foreach ($booleanFields as $field) {
                if (isset($this->formData[$field])) {
                    $this->formData[$field] = $this->formData[$field] === 'Si' ? 'Si' : 'No';
                } else {
                    $this->formData[$field] = 'No';
                }
            }

            // Sanitizar strings
            $stringFields = ['encabezado', 'evento', 'ciudad'];
            foreach ($stringFields as $field) {
                if (isset($this->formData[$field])) {
                    $this->formData[$field] = htmlspecialchars(
                        trim($this->formData[$field]),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                }
            }

            // Validar fecha
            if (!empty($this->formData['fecha'])) {
                $fecha = DateTime::createFromFormat('Y-m-d', $this->formData['fecha']);
                if (!$fecha || $fecha->format('Y-m-d') !== $this->formData['fecha']) {
                    throw new Exception("El formato de fecha no es válido");
                }
            }

            // Validar presentación del artista
            if (isset($this->formData['presentacion_artista'])) {
                $this->formData['presentacion_artista'] = trim($this->formData['presentacion_artista']);
                if (empty($this->formData['presentacion_artista'])) {
                    $this->formData['presentacion_artista'] = "Agradecemos desde ya su interés en el espectáculo...";
                }
            } else {
                $this->formData['presentacion_artista'] = "Agradecemos desde ya su interés en el espectáculo...";
            }

            $this->logger->log("Datos del formulario validados correctamente");
            return true;
        } catch (Exception $e) {
            $this->logger->log("Error en validación de datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea la página de portada
     */
    private function createCoverPage()
    {
        $this->logger->log("Creando página de portada");
        $section = $this->phpWord->addSection($this->getPageSettings());
        $this->addBackgroundImage($section, 'portada');
    }

    /**
     * Crea la segunda página con información principal
     */
    private function createSecondPage()
    {
        $this->logger->log("Creando segunda página");
        $section = $this->phpWord->addSection($this->getPageSettings(true));
        $this->addBackgroundImage($section, 'hoja2');

        $section->addText(
            'COTIZACIÓN ARTÍSTICA',
            'titleStyle',
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        $saludo = $this->formData['es_encabezado_evento'] ? 'Señores(as):' : 'Señor(a):';
        $section->addText($saludo, 'subtitleStyle', ['alignment' => Jc::LEFT]);
        $section->addText(
            htmlspecialchars($this->formData['encabezado']),
            'subtitleStyle',
            ['alignment' => Jc::LEFT]
        );

        $section->addTextBreak(1);
        $this->addDescriptiveText($section);
    }

    /**
     * Crea la tercera página con detalles del evento
     */
    private function createThirdPage()
    {
        $this->logger->log("Creando tercera página");
        try {
            $section = $this->phpWord->addSection($this->getPageSettings(true));
            $this->addBackgroundImage($section, 'hoja3');

            $section->addText(
                "A continuación, paso a detallar en extenso nuestra propuesta comercial y las condiciones " .
                    "para la realización de una presentación de nuestro artista en su evento.",
                'paragraphStyle',
                ['alignment' => Jc::BOTH]
            );
            $section->addTextBreak(1);

            $this->addEventDetails($section);
            $this->addIncludedItems($section);
            $this->addAdditionalItems($section);
        } catch (Exception $e) {
            $this->logger->log("Error en tercera página: " . $e->getMessage());
            throw new Exception("Error al generar la tercera página: " . $e->getMessage());
        }
    }

    /**
     * Crea la cuarta página con términos y condiciones
     */
    private function createFourthPage()
    {
        $this->logger->log("Creando cuarta página");
        try {
            $section = $this->phpWord->addSection($this->getPageSettings(true));
            $this->addBackgroundImage($section, 'hoja4');

            $this->addTermsAndConditions($section);

            $section->addTextBreak(10);
            $section->addText(
                "Esperando cumplir con sus expectativas, quedo atenta a sus comentarios.",
                'finalText',
                ['alignment' => Jc::CENTER]
            );
        } catch (Exception $e) {
            $this->logger->log("Error en cuarta página: " . $e->getMessage());
            throw new Exception("Error al generar la cuarta página: " . $e->getMessage());
        }
    }

    /**
     * Añade el texto descriptivo principal
     */
    private function addDescriptiveText($section)
    {
        $presentacion = !empty($this->formData['presentacion_artista'])
            ? $this->formData['presentacion_artista']
            : "Agradecemos desde ya su interés en el espectáculo..."; // Texto por defecto en caso de que no haya presentación

        $section->addText($presentacion, 'paragraphStyle', ['alignment' => Jc::BOTH]);
    }

    /**
     * Agrega los términos y condiciones
     */
    private function addTermsAndConditions($section)
    {
        $terms = [
            "La contratación adicional de locación, equipos técnicos de audio, iluminación, video, " .
                "pantallas y sus respectivos operadores y técnicos, camarines, catering, seguridad privada, " .
                "permisos municipales, sanitarios y/o de otra índole y todo los que sea necesario para la " .
                "correcta puesta en escena y funcionamiento del show solicitado, son de exclusiva " .
                "responsabilidad y costo del contratante.",

            "La reserva de fecha y horario de los servicios del artista se dará por entendida única " .
                "y exclusivamente al momento de la firma de contrato y orden de compra.",

            "Forma de pago: Se acepta dinero en efectivo (moneda nacional), transferencia bancaria " .
                "y cheque al día únicamente a Municipalidades."
        ];

        foreach ($terms as $term) {
            $section->addText($term, 'normalText', ['alignment' => Jc::BOTH]);
            $section->addTextBreak(1);
        }
    }

    private function addEventDetails($section)
    {
        try {
            $eventDetails = [
                "Evento: " . htmlspecialchars($this->formData['evento']),
                "Ciudad: " . htmlspecialchars($this->formData['ciudad']),
            ];

            // Agregar fecha solo si está definida y no es la fecha actual por defecto
            if (!empty($this->formData['fecha']) && $this->formData['fecha'] !== date('Y-m-d')) {
                $formattedDate = $this->formatDate($this->formData['fecha']);
                $eventDetails[] = "Fecha: " . $formattedDate;
            }

            // Manejar la hora
            $hora = $this->formatTime($this->formData['horario']);
            $eventDetails[] = "Hora: " . $hora;

            // Formatear y agregar el valor
            $formattedValue = $this->formatValue($this->formData['valor']);
            $eventDetails[] = "Valor: $" . $formattedValue;

            foreach ($eventDetails as $detail) {
                $section->addText($detail, 'boldParagraphStyle');
            }

            $section->addTextBreak(1);
        } catch (Exception $e) {
            $this->logger->log("Error al agregar detalles del evento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Formatea la hora al formato deseado
     */
    private function formatTime($time)
    {
        try {
            if ($time === null) {
                return 'Por definir';
            }

            // Intentar parsear la hora
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                return date('H:i', $timestamp);
            }

            // Si no se puede parsear, devolver la hora original
            return $time;
        } catch (Exception $e) {
            $this->logger->log("Error al formatear hora: " . $e->getMessage());
            return $time;
        }
    }


    /**
     * Agrega los items incluidos en la cotización
     */
    private function addIncludedItems($section)
    {
        try {
            $section->addText("La presente cotización incluye:", 'boldParagraphStyle');

            // Crear estilo de lista numerada
            $bulletListStyle = array(
                'spacing' => 100,
                'spaceAfter' => 60,
            );

            // Crear estilo de fuente para los items
            $fontStyle = array(
                'name' => FONT_LATO_LIGHT,
                'size' => DEFAULT_FONT_SIZE,
            );

            $items = array_merge(
                $this->getIncludedRadioItems(),
                [
                    "Ejecución de un Show en vivo.",
                    "Duración aproximada de 60 minutos (incluido BIS)."
                ]
            );

            foreach ($items as $item) {
                $listItem = $section->addListItem($item, 0);
                $listItem->getTextObject()->setParagraphStyle($bulletListStyle);
                $listItem->getTextObject()->getFontStyle()->setName($fontStyle['name']);
                $listItem->getTextObject()->getFontStyle()->setSize($fontStyle['size']);
            }

            $section->addTextBreak(1);
        } catch (Exception $e) {
            $this->logger->log("Error al agregar items incluidos: " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Obtiene los items incluidos basados en las selecciones de radio
     */
    private function getIncludedRadioItems()
    {
        $items = [];
        if ($this->formData['hotel'] === 'Si') {
            $items[] = "Hotel.";
        }
        if ($this->formData['transporte'] === 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta.";
        }
        if ($this->formData['viaticos'] === 'Si') {
            $items[] = "Viáticos.";
        }
        return $items;
    }

    /**
     * Agrega los items adicionales a cargo del productor
     */
    private function addAdditionalItems($section)
    {
        try {
            $section->addText(
                "Costos Logísticos adicionales a cubrir por el Productor Local:",
                'boldParagraphStyle'
            );

            // Crear estilo de lista numerada
            $bulletListStyle = array(
                'spacing' => 100,
                'spaceAfter' => 60,
            );

            // Crear estilo de fuente para los items
            $fontStyle = array(
                'name' => FONT_LATO_LIGHT,
                'size' => DEFAULT_FONT_SIZE,
            );

            $items = array_merge(
                $this->getAdditionalItems(),
                [
                    "Catering y Camarines.",
                    "Rider Técnico y logístico.",
                    "Prueba de sonido con un tiempo efectivo, mínimo de 1 hora sin acceso de público general."
                ]
            );

            foreach ($items as $item) {
                $listItem = $section->addListItem($item, 0);
                $listItem->getTextObject()->setParagraphStyle($bulletListStyle);
                $listItem->getTextObject()->getFontStyle()->setName($fontStyle['name']);
                $listItem->getTextObject()->getFontStyle()->setSize($fontStyle['size']);
            }
        } catch (Exception $e) {
            $this->logger->log("Error al agregar items adicionales: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los items adicionales basados en las selecciones de radio
     */
    private function getAdditionalItems()
    {
        $items = [];
        if ($this->formData['hotel'] !== 'Si') {
            $items[] = "Hotel.";
        }
        if ($this->formData['transporte'] !== 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta.";
        }
        if ($this->formData['viaticos'] !== 'Si') {
            $items[] = "Viáticos.";
        }
        return $items;
    }

    /**
     * Establece el nombre del archivo
     */
    private function setFileName()
    {
        try {
            // Obtener y formatear el nombre del cliente
            $clientName = trim($this->formData['nombres'] . ' ' . $this->formData['apellidos']);
            // Capitalizar cada palabra del nombre del cliente
            $clientName = mb_convert_case($clientName, MB_CASE_TITLE, 'UTF-8');

            // Construir el nombre del archivo con el formato deseado
            $nombreArchivo = "Cotización " . $clientName;

            // Asegurarse de que el nombre del archivo sea válido para el sistema de archivos
            // pero manteniendo tildes y ñ
            $nombreArchivo = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $nombreArchivo);

            $this->fileName = $nombreArchivo . '.docx';
        } catch (Exception $e) {
            $this->fileName = "Cotización.docx";
        }
    }
    /**
     * Obtiene la configuración de página
     */
    private function getPageSettings($withMargins = false)
    {
        $settings = [
            'orientation' => 'portrait',
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(11),
        ];

        if ($withMargins) {
            $settings = array_merge($settings, [
                'marginTop' => 800,
                'marginLeft' => 800,
                'marginRight' => 800,
                'marginBottom' => 800
            ]);
        } else {
            $settings = array_merge($settings, [
                'marginTop' => 0,
                'marginLeft' => 0,
                'marginRight' => 0,
                'marginBottom' => 0
            ]);
        }

        return $settings;
    }

    /**
     * Añade una imagen de fondo a la sección
     */
    private function addBackgroundImage($section, $imageName)
    {
        try {
            $imagePath = $this->config['background_images'][$imageName];

            if (!file_exists($imagePath)) {
                throw new Exception("Imagen no encontrada: $imagePath");
            }

            $section->addImage(
                $imagePath,
                [
                    'width' => 612,
                    'height' => 792,
                    'positioning' => Image::POSITION_ABSOLUTE,
                    'posHorizontal' => Image::POSITION_HORIZONTAL_CENTER,
                    'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
                    'posVertical' => Image::POSITION_VERTICAL_TOP,
                    'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
                    'wrappingStyle' => 'behind'
                ]
            );
        } catch (Exception $e) {
            $this->logger->log("Error al añadir imagen de fondo: " . $e->getMessage());
            throw new Exception("Error al añadir imagen de fondo: " . $e->getMessage());
        }
    }

    /**
     * Formatea la fecha al estilo español
     */
    private function formatDate($date)
    {
        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                throw new Exception("Formato de fecha inválido");
            }

            $formattedDate = date('d \d\e F \d\e\l Y', $timestamp);

            $months = [
                'January' => 'Enero',
                'February' => 'Febrero',
                'March' => 'Marzo',
                'April' => 'Abril',
                'May' => 'Mayo',
                'June' => 'Junio',
                'July' => 'Julio',
                'August' => 'Agosto',
                'September' => 'Septiembre',
                'October' => 'Octubre',
                'November' => 'Noviembre',
                'December' => 'Diciembre'
            ];

            return str_replace(array_keys($months), array_values($months), $formattedDate);
        } catch (Exception $e) {
            $this->logger->log("Error al formatear fecha: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Formatea el valor monetario
     */
    private function formatValue($value)
    {
        try {
            if (!is_numeric($value)) {
                throw new Exception("El valor no es numérico");
            }
            return number_format($value, 0, ',', '.') . ' IVA Incluido';
        } catch (Exception $e) {
            $this->logger->log("Error al formatear valor: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Guarda el documento generado
     */
    private function saveDocument()
    {
        $this->logger->log("Iniciando proceso de guardado del documento");

        try {
            // Limpiar todos los buffers de salida
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Crear archivo temporal con extensión .docx
            $tempFile = tempnam($this->config['temp_directory'], 'quote_');
            $tempFileDocx = $tempFile . '.docx';

            if ($tempFile === false) {
                throw new Exception("No se pudo crear el archivo temporal");
            }

            // Renombrar el archivo temporal para agregarle la extensión .docx
            if (file_exists($tempFile)) {
                rename($tempFile, $tempFileDocx);
            }

            // Guardar documento en archivo temporal
            $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
            $writer->save($tempFileDocx);

            // Verificar que el archivo se creó correctamente
            if (!file_exists($tempFileDocx) || filesize($tempFileDocx) === 0) {
                throw new Exception("Error al generar el archivo");
            }

            // Enviar headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
            header('Cache-Control: max-age=0');
            header('Content-Length: ' . filesize($tempFileDocx));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // Leer y enviar el archivo
            readfile($tempFileDocx);

            // Eliminar archivos temporales
            if (file_exists($tempFileDocx)) {
                unlink($tempFileDocx);
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            $this->logger->log("Documento guardado y enviado exitosamente");
            exit(); // Asegurarnos de que no se envíe nada más

        } catch (Exception $e) {
            $this->logger->log("Error al guardar documento: " . $e->getMessage());
            throw new Exception("Error al guardar el documento: " . $e->getMessage());
        }
    }
    // Por brevedad, no incluyo todos los métodos aquí, pero deberían copiarse
    // todos los métodos de las secciones anteriores dentro de esta única definición de clase
}

/**
 * Clase para manejar la conexión a la base de datos
 */
class DatabaseConnection
{
    private $conn;
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->conn = getDbConnection();
        $this->logger->log("Conexión a base de datos establecida exitosamente");
    }

    /**
     * Obtiene los datos completos del evento
     * 
     * @param int $evento_id ID del evento a consultar
     * @return array Datos del evento
     * @throws Exception Si hay error en la consulta o no se encuentra el evento
     */
    public function getEventData($evento_id)
    {
        try {
            // Validar el ID del evento
            $evento_id = filter_var($evento_id, FILTER_VALIDATE_INT);
            if ($evento_id === false) {
                throw new Exception("ID de evento inválido");
            }

            // Consulta principal con todos los JOINs necesarios
            $sql = "SELECT 
                e.*,
                COALESCE(c.nombres, '') as nombres,
                COALESCE(c.apellidos, '') as apellidos,
                COALESCE(c.rut, '') as rut_cliente,
                COALESCE(c.correo, '') as correo,
                COALESCE(c.celular, '') as celular,
                COALESCE(emp.nombre, '') as nombre_empresa,
                COALESCE(emp.rut, '') as rut_empresa,
                COALESCE(e.encabezado_evento, '') as encabezado_evento,
                COALESCE(a.presentacion, '') as presentacion_artista,
                CASE 
                    WHEN e.hora_evento IS NULL THEN 'Por definir'
                    WHEN e.hora_evento = '' THEN 'Por definir'
                    ELSE TIME_FORMAT(e.hora_evento, '%H:%i')
                END as hora_evento,
                e.artista_id,
                COALESCE(e.ciudad_evento, 'Por definir') as ciudad_evento,
                COALESCE(e.lugar_evento, 'Por definir') as lugar_evento,
                COALESCE(e.valor_evento, 0) as valor_evento,
                COALESCE(e.hotel, 'No') as hotel,
                COALESCE(e.traslados, 'No') as traslados,
                COALESCE(e.viaticos, 'No') as viaticos
            FROM eventos e 
            LEFT JOIN clientes c ON e.cliente_id = c.id 
            LEFT JOIN empresas emp ON c.id = emp.cliente_id
            LEFT JOIN artistas a ON e.artista_id = a.id
            WHERE e.id = ?";

            // Preparar y ejecutar la consulta
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $this->conn->error);
            }

            $stmt->bind_param("i", $evento_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("No se encontró el evento especificado (ID: $evento_id)");
            }

            // Obtener los datos
            $evento = $result->fetch_assoc();
            $stmt->close();

            // Procesamiento adicional de datos

            // Formatear la hora si existe
            if ($evento['hora_evento'] !== 'Por definir') {
                $hora = DateTime::createFromFormat('H:i', $evento['hora_evento']);
                if ($hora) {
                    $evento['hora_evento'] = $hora->format('H:i');
                }
            }

            // Formatear la fecha si existe
            if (!empty($evento['fecha_evento'])) {
                $fecha = DateTime::createFromFormat('Y-m-d', $evento['fecha_evento']);
                if ($fecha) {
                    $evento['fecha_evento'] = $fecha->format('Y-m-d');
                }
            } else {
                $evento['fecha_evento'] = date('Y-m-d');
            }

            // Asegurar valores booleanos correctos
            $booleanFields = ['hotel', 'traslados', 'viaticos'];
            foreach ($booleanFields as $field) {
                $evento[$field] = $evento[$field] === 'Si' ? 'Si' : 'No';
            }

            // Asegurar que el valor del evento sea numérico
            $evento['valor_evento'] = is_numeric($evento['valor_evento'])
                ? (int)$evento['valor_evento']
                : 0;

            // Sanitizar campos de texto
            $textFields = ['nombres', 'apellidos', 'ciudad_evento', 'lugar_evento', 'encabezado_evento'];
            foreach ($textFields as $field) {
                if (isset($evento[$field])) {
                    $evento[$field] = htmlspecialchars(
                        trim($evento[$field]),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                }
            }

            // Log del éxito de la operación
            $this->logger->log("Datos del evento $evento_id recuperados exitosamente");

            return $evento;
        } catch (Exception $e) {
            $this->logger->log("Error al obtener datos del evento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza los datos de un evento
     */
    public function updateEvent($evento_id, $datos)
    {
        try {
            $sql = "UPDATE eventos SET 
                    nombre_evento = ?,
                    ciudad_evento = ?,
                    fecha_evento = ?,
                    hora_evento = ?,
                    valor_evento = ?,
                    hotel = ?,
                    traslados = ?,
                    viaticos = ?,
                    encabezado_evento = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la actualización: " . $this->conn->error);
            }

            $stmt->bind_param(
                "sssssssssi",
                $datos['nombre_evento'],
                $datos['ciudad_evento'],
                $datos['fecha_evento'],
                $datos['hora_evento'],
                $datos['valor_evento'],
                $datos['hotel'],
                $datos['traslados'],
                $datos['viaticos'],
                $datos['encabezado_evento'],
                $evento_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el evento: " . $stmt->error);
            }

            $stmt->close();
            $this->logger->log("Evento actualizado exitosamente");

            return true;
        } catch (Exception $e) {
            $this->logger->log("Error al actualizar evento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inserta un nuevo evento
     */
    public function insertEvent($datos)
    {
        try {
            $sql = "INSERT INTO eventos (
                    cliente_id,
                    nombre_evento,
                    ciudad_evento,
                    fecha_evento,
                    hora_evento,
                    valor_evento,
                    hotel,
                    traslados,
                    viaticos,
                    encabezado_evento,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la inserción: " . $this->conn->error);
            }

            $stmt->bind_param(
                "isssssssss",
                $datos['cliente_id'],
                $datos['nombre_evento'],
                $datos['ciudad_evento'],
                $datos['fecha_evento'],
                $datos['hora_evento'],
                $datos['valor_evento'],
                $datos['hotel'],
                $datos['traslados'],
                $datos['viaticos'],
                $datos['encabezado_evento']
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al insertar el evento: " . $stmt->error);
            }

            $nuevo_id = $stmt->insert_id;
            $stmt->close();

            $this->logger->log("Nuevo evento insertado con ID: " . $nuevo_id);

            return $nuevo_id;
        } catch (Exception $e) {
            $this->logger->log("Error al insertar evento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un evento
     */
    public function deleteEvent($evento_id)
    {
        try {
            $sql = "DELETE FROM eventos WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la eliminación: " . $this->conn->error);
            }

            $stmt->bind_param("i", $evento_id);

            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar el evento: " . $stmt->error);
            }

            $stmt->close();
            $this->logger->log("Evento eliminado exitosamente");

            return true;
        } catch (Exception $e) {
            $this->logger->log("Error al eliminar evento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cierra la conexión a la base de datos
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Código principal de ejecución
try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['id'])) {
        // Obtener ID del evento
        $evento_id = isset($_POST['evento_id']) ? $_POST['evento_id'] : $_GET['id'];

        // Obtener datos del evento
        $db = new DatabaseConnection();
        $evento = $db->getEventData($evento_id);

        // Preparar datos para la cotización
        $formData = [
            'encabezado' => !empty($evento['encabezado_evento'])
                ? $evento['encabezado_evento']
                : ($evento['nombres'] . ' ' . $evento['apellidos']),
            'nombres' => $evento['nombres'],
            'apellidos' => $evento['apellidos'],
            'es_encabezado_evento' => !empty($evento['encabezado_evento']),
            'ciudad' => $evento['ciudad_evento'],
            'fecha' => $evento['fecha_evento'],
            'horario' => $evento['hora_evento'],
            'evento' => $evento['nombre_evento'],
            'valor' => $evento['valor_evento'],
            'hotel' => $evento['hotel'],
            'transporte' => $evento['traslados'],
            'viaticos' => $evento['viaticos'],
            'presentacion_artista' => $evento['presentacion_artista']
        ];

        // Generar cotización
        $quoteGenerator = new QuoteGenerator($formData, $config);
        $quoteGenerator->generate();
    } else {
        throw new Exception("Método de solicitud no válido");
    }
} catch (Exception $e) {
    error_log("Error en la generación de la cotización: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
