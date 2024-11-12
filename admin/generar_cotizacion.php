<?php
// Configuración de errores y memoria
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M');

// Incluir autoloader de Composer
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
        'hoja2' => 'assets/img/hoja2.png',
        'hoja3' => 'assets/img/hoja3.png',
        'hoja4' => 'assets/img/hoja4.png',
    ],
    'temp_directory' => sys_get_temp_dir(),
];

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
                'ciudad' => 'Ciudad',
                'fecha' => 'Fecha',
                'horario' => 'Horario',
                'valor' => 'Valor'
            ];

            foreach ($required_fields as $field => $label) {
                if (empty($this->formData[$field])) {
                    throw new Exception("El campo '$label' es requerido");
                }
            }

            // Validar fecha
            if (!strtotime($this->formData['fecha'])) {
                throw new Exception("La fecha proporcionada no es válida");
            }

            // Validar hora - Versión más flexible
            $hora = $this->formData['horario'];

            // Limpiar la hora de espacios y convertir a formato 24h
            $hora = trim($hora);

            // Si la hora viene con formato AM/PM, convertirla a 24h
            if (stripos($hora, 'am') !== false || stripos($hora, 'pm') !== false) {
                $hora = date('H:i', strtotime($hora));
            }

            // Permitir varios formatos de hora
            if (
                !preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/', $hora) &&
                !preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3])[:.]([0-5][0-9])$/', $hora)
            ) {

                // Intentar convertir la hora a un formato válido
                $timestamp = strtotime($hora);
                if ($timestamp === false) {
                    throw new Exception("El formato de hora no es válido. Use formato 24h (ejemplo: 14:30) o 12h (ejemplo: 02:30 PM)");
                }
                $hora = date('H:i', $timestamp);
            }

            // Actualizar el horario en formData con el formato correcto
            $this->formData['horario'] = $hora;

            // Validar valor monetario
            if (!is_numeric($this->formData['valor']) || $this->formData['valor'] <= 0) {
                throw new Exception("El valor debe ser un número positivo");
            }

            $this->logger->log("Datos del formulario validados correctamente");
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
        $paragraph = "Agradecemos desde ya su interés en el espectáculo de la reconocida banda Argentina, " .
            "Agrupación Marilyn. Sin duda, esta banda representa una experiencia musical integral " .
            "con una destacada trayectoria. Agrupación Marilyn ha conseguido un lugar especial " .
            "en el corazón de seguidores tanto a nivel nacional como internacional. Su música, " .
            "definida por la cumbia romántica y testimonial, narra historias que reflejan el " .
            "cotidiano vivir con las cuales todos podemos identificarnos. Entre sus éxitos " .
            "destacan Su florcita, Me enamoré, Te falta sufrir y Madre soltera. Actualmente, " .
            "Agrupación Marilyn trabaja en su sexto disco, del cual ya han lanzado los exitosos " .
            "singles: Abismo, Siento y Piel y Huesos, que adelantan una propuesta fresca y " .
            "poderosa, fiel a su estilo.";

        $section->addText($paragraph, 'paragraphStyle', ['alignment' => Jc::BOTH]);
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

    /**
     * Agrega los detalles del evento a la sección
     */
    private function addEventDetails($section)
    {
        try {
            $formattedDate = $this->formatDate($this->formData['fecha']);
            $formattedTime = date('H:i', strtotime($this->formData['horario']));
            $formattedValue = $this->formatValue($this->formData['valor']);

            $eventDetails = [
                "Evento: " . htmlspecialchars($this->formData['evento']),
                "Ciudad: " . htmlspecialchars($this->formData['ciudad']),
                "Fecha: " . $formattedDate,
                "Hora: " . $formattedTime,
                "Valor: $" . $formattedValue
            ];

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
                    "Ejecución de un Show en vivo: 1 vocalista + 4 músicos.",
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
            $items[] = "Hotel para 12 personas.";
        }
        if ($this->formData['transporte'] === 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta (12 personas).";
        }
        if ($this->formData['viaticos'] === 'Si') {
            $items[] = "Viáticos para (12 personas).";
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
            $items[] = "Hotel para 12 personas.";
        }
        if ($this->formData['transporte'] !== 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta (12 personas).";
        }
        if ($this->formData['viaticos'] !== 'Si') {
            $items[] = "Viáticos para (12 personas).";
        }
        return $items;
    }

    /**
     * Establece el nombre del archivo
     */
    private function setFileName()
    {
        // Usar solo nombres y apellidos del cliente
        $clientName = trim($this->formData['nombres'] . ' ' . $this->formData['apellidos']);

        // Limpia el nombre del cliente de caracteres especiales y espacios
        $cleanName = preg_replace('/[^A-Za-z0-9\s]/', '', $clientName);
        // Reemplaza espacios múltiples por uno solo y convierte a minúsculas
        $cleanName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $cleanName)));
        // Reemplaza espacios por guiones bajos
        $cleanName = str_replace(' ', '_', $cleanName);
        // Construye el nombre final del archivo
        $this->fileName = 'cotizacion_' . $cleanName . '.docx';
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
            // Limpiar cualquier salida anterior
            if (ob_get_length()) ob_end_clean();

            // Crear archivo temporal
            $tempFile = tempnam($this->config['temp_directory'], 'quote_');
            if ($tempFile === false) {
                throw new Exception("No se pudo crear el archivo temporal");
            }

            // Guardar documento en archivo temporal
            $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
            $writer->save($tempFile);

            // Verificar que el archivo se creó correctamente
            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                throw new Exception("Error al generar el archivo");
            }

            // Enviar headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
            header('Cache-Control: max-age=0');

            // Leer y enviar el archivo
            readfile($tempFile);

            // Eliminar archivo temporal
            unlink($tempFile);

            $this->logger->log("Documento guardado y enviado exitosamente");
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
        $this->connect();
    }

    /**
     * Establece la conexión a la base de datos
     */
    private function connect()
    {
        try {
            $config = [
                'host' => 'localhost',
                'username' => 'root',
                'password' => '',
                'database' => 'schaaf_producciones'
            ];

            $this->conn = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database']
            );

            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión: " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8mb4");
            $this->logger->log("Conexión a base de datos establecida exitosamente");
        } catch (Exception $e) {
            $this->logger->log("Error de conexión a la base de datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los datos del evento
     */
    public function getEventData($evento_id)
    {
        try {
            $sql = "SELECT e.*, c.nombres, c.apellidos, c.rut as rut_cliente, 
                           c.correo, c.celular, emp.nombre as nombre_empresa, 
                           emp.rut as rut_empresa, e.encabezado_evento
                    FROM eventos e 
                    LEFT JOIN clientes c ON e.cliente_id = c.id 
                    LEFT JOIN empresas emp ON c.id = emp.cliente_id
                    WHERE e.id = ?";

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
                throw new Exception("No se encontró el evento especificado");
            }

            $evento = $result->fetch_assoc();
            $stmt->close();

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
            'viaticos' => $evento['viaticos']
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
