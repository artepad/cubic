<?php
// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir autoloader de Composer
require_once 'vendor/autoload.php';

// Importar clases necesarias
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Image;

// Definir constantes
define('FONT_LATO', 'Lato');
define('FONT_LATO_LIGHT', 'Lato Light');
define('COLOR_BLUE', '1F4E79');
define('DEFAULT_FONT_SIZE', 18);

// Configuración
$config = [
    'background_images' => [
        'portada' => 'assets/img/portada.png',
        'hoja2' => 'assets/img/hoja2.png',
        'hoja3' => 'assets/img/hoja3.png',
        'hoja4' => 'assets/img/hoja4.png',
    ],
];

/**
 * Clase principal para generar la cotización
 */
class QuoteGenerator
{
    private $phpWord;
    private $formData;
    private $config;
    private $fileName;

    public function __construct($formData, $config)
    {
        $this->phpWord = new PhpWord();
        $this->formData = $formData;
        $this->config = $config;
        $this->setupDocument();
        $this->setFileName();
    }

    private function setupDocument()
    {
        $this->phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ES_ES));
        $this->defineStyles();
    }

    private function defineStyles()
    {
        $this->phpWord->addFontStyle('titleStyle', ['name' => FONT_LATO, 'size' => 23, 'color' => COLOR_BLUE, 'bold' => true]);
        $this->phpWord->addFontStyle('subtitleStyle', ['name' => FONT_LATO_LIGHT, 'size' => 21, 'bold' => true]);
        $this->phpWord->addFontStyle('paragraphStyle', ['name' => FONT_LATO_LIGHT, 'size' => DEFAULT_FONT_SIZE, 'bold' => false]);
        $this->phpWord->addFontStyle('boldParagraphStyle', ['name' => FONT_LATO_LIGHT, 'size' => 20, 'bold' => true]);
        $this->phpWord->addFontStyle('normalText', ['name' => FONT_LATO_LIGHT, 'size' => DEFAULT_FONT_SIZE]);
        $this->phpWord->addFontStyle('finalText', ['name' => FONT_LATO, 'size' => DEFAULT_FONT_SIZE, 'italic' => true, 'color' => COLOR_BLUE, 'bold' => true]);
    }

    public function generate()
    {
        $this->createCoverPage();
        $this->createSecondPage();
        $this->createThirdPage();
        $this->createFourthPage();
        $this->saveDocument();
    }

    private function createCoverPage()
    {
        $section = $this->phpWord->addSection($this->getPageSettings());
        $this->addBackgroundImage($section, 'portada');
    }

    private function createSecondPage()
    {
        $section = $this->phpWord->addSection($this->getPageSettings(true));
        $this->addBackgroundImage($section, 'hoja2');

        $section->addText('COTIZACIÓN ARTÍSTICA', 'titleStyle', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTextBreak(1);

        // Determinar el saludo basado en si es un encabezado de evento o nombre de cliente
        $saludo = $this->formData['es_encabezado_evento'] ? 'Señores(as):' : 'Señor(a):';
        $section->addText($saludo, 'subtitleStyle', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $section->addText(htmlspecialchars($this->formData['encabezado']), 'subtitleStyle', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

        $section->addTextBreak(1);

        $paragraph = "Agradecemos desde ya su interés en el espectáculo de la reconocida banda Argentina, Agrupación Marilyn. Sin duda, esta banda representa una experiencia musical integral con una destacada trayectoria. Agrupación Marilyn ha conseguido un lugar especial en el corazón de seguidores tanto a nivel nacional como internacional. Su música, definida por la cumbia romántica y testimonial, narra historias que reflejan el cotidiano vivir con las cuales todos podemos identificarnos. Entre sus éxitos destacan Su florcita, Me enamoré, Te falta sufrir y Madre soltera. Actualmente, Agrupación Marilyn trabaja en su sexto disco, del cual ya han lanzado los exitosos singles: Abismo, Siento y Piel y Huesos, que adelantan una propuesta fresca y poderosa, fiel a su estilo.";

        $section->addText($paragraph, 'paragraphStyle', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
    }

    private function createThirdPage()
    {
        $section = $this->phpWord->addSection($this->getPageSettings(true));
        $this->addBackgroundImage($section, 'hoja3');

        $newParagraph = "A continuación, paso a detallar en extenso nuestra propuesta comercial y las condiciones para la realización de una presentación de nuestro artista en su evento.";

        $section->addText($newParagraph, 'paragraphStyle', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
        $section->addTextBreak(1);

        $this->addFormData($section);
        $this->addIncludedItems($section);
        $this->addAdditionalItems($section);
    }

    private function createFourthPage()
    {
        $section = $this->phpWord->addSection($this->getPageSettings(true));
        $this->addBackgroundImage($section, 'hoja4');

        $paragraphs = [
            "La contratación adicional de locación, equipos técnicos de audio, iluminación, video, pantallas y sus respectivos operadores y técnicos, camarines, catering, seguridad privada, permisos municipales, sanitarios y/o de otra índole y todo los que sea necesario para la correcta puesta en escena y funcionamiento del show solicitado, son de exclusiva responsabilidad y costo del contratante.",
            "La reserva de fecha y horario de los servicios del artista se dará por entendida única y exclusivamente al momento de la firma de contrato y orden de compra.",
            "Forma de pago: Se acepta dinero en efectivo (moneda nacional), transferencia bancaria y cheque al día únicamente a Municipalidades.",
        ];

        foreach ($paragraphs as $paragraph) {
            $section->addText($paragraph, 'normalText', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
            $section->addTextBreak(1);
        }

        $section->addTextBreak(10);

        $finalParagraph = "Esperando cumplir con sus expectativas, quedo atenta a sus comentarios.";
        $section->addText($finalParagraph, 'finalText', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    }

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

    private function addBackgroundImage($section, $imageName)
    {
        $imagePath = $this->config['background_images'][$imageName];
        if (!file_exists($imagePath)) {
            throw new \Exception("Error: La imagen de fondo no se encuentra en la ruta especificada: $imagePath");
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
    }

    private function addFormData($section)
    {
        $formattedDate = $this->formatDate($this->formData['fecha']);
        $formattedTime = date('H:i', strtotime($this->formData['horario']));
        $formattedValue = $this->formatValue($this->formData['valor']);

        $formData = [
            "Evento: " . htmlspecialchars($this->formData['evento']),
            "Ciudad: " . htmlspecialchars($this->formData['ciudad']),
            "Fecha: " . $formattedDate,
            "Hora: " . $formattedTime,
            "Valor: $" . $formattedValue,
        ];

        foreach ($formData as $item) {
            $section->addText($item, 'boldParagraphStyle');
        }

        $section->addTextBreak(1);
    }

    private function addIncludedItems($section)
    {
        $section->addText("La presente cotización incluye:", 'boldParagraphStyle');

        $itemsIncluidosRadio = $this->getIncludedRadioItems();
        $itemsIncluidosFijos = [
            "Ejecución de un Show en vivo: 1 vocalista + 4 músicos.",
            "Duración aproximada de 60 minutos (incluido BIS)."
        ];

        foreach (array_merge($itemsIncluidosRadio, $itemsIncluidosFijos) as $item) {
            $section->addListItem($item, 0, 'paragraphStyle');
        }

        $section->addTextBreak(1);
    }

    private function addAdditionalItems($section)
    {
        $section->addText("Costos Logísticos adicionales a cubrir por el Productor Local:", 'boldParagraphStyle');

        $itemsAdicionales = $this->getAdditionalItems();
        $itemsAdicionalesFijos = [
            "Catering y Camarines.",
            "Rider Técnico y logístico.",
            "Prueba de sonido con un tiempo efectivo, mínimo de 1 hora sin acceso de público general."
        ];

        foreach (array_merge($itemsAdicionales, $itemsAdicionalesFijos) as $item) {
            $section->addListItem($item, 0, 'paragraphStyle');
        }
    }

    private function getIncludedRadioItems()
    {
        $items = [];
        if ($this->formData['hotel'] == 'Si') {
            $items[] = "Hotel para 12 personas.";
        }
        if ($this->formData['transporte'] == 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta (12 personas).";
        }
        if ($this->formData['viaticos'] == 'Si') {
            $items[] = "Viáticos para (12 personas).";
        }
        return $items;
    }

    private function getAdditionalItems()
    {
        $items = [];
        if ($this->formData['hotel'] != 'Si') {
            $items[] = "Hotel para 12 personas.";
        }
        if ($this->formData['transporte'] != 'Si') {
            $items[] = "Traslados de la Banda y Staff, ida y vuelta (12 personas).";
        }
        if ($this->formData['viaticos'] != 'Si') {
            $items[] = "Viáticos para (12 personas).";
        }
        return $items;
    }

    private function formatDate($date)
    {
        $formattedDate = date('d \d\e F \d\e\l Y', strtotime($date));
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
    }

    private function formatValue($value)
    {
        return number_format($value, 0, ',', '.') . ' IVA Incluido';
    }

    private function setFileName()
    {
        // Limpiar el encabezado de caracteres especiales y espacios
        $cleanName = preg_replace('/[^A-Za-z0-9]/', '_', $this->formData['encabezado']);
        $cleanName = strtolower(trim($cleanName, '_'));
        $this->fileName = 'cotizacion_' . $cleanName . '.docx';
    }

    private function saveDocument()
    {
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
        header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        header("Content-Disposition: attachment; filename=" . $this->fileName);
        header("Cache-Control: max-age=0");
        $writer->save("php://output");
    }
}

// Verificar si se recibió una solicitud POST o GET
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['id'])) {
    try {
        // Obtener el ID del evento
        $evento_id = isset($_POST['evento_id']) ? $_POST['evento_id'] : $_GET['id'];

        // Conexión a la base de datos
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "schaaf_producciones";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Conexión fallida: " . $conn->connect_error);
        }

        // Consulta para obtener los datos del evento
        $sql = "SELECT e.*, c.nombres, c.apellidos, c.rut as rut_cliente, c.correo, c.celular, 
        emp.nombre as nombre_empresa, emp.rut as rut_empresa, e.encabezado_evento
        FROM eventos e 
        LEFT JOIN clientes c ON e.cliente_id = c.id 
        LEFT JOIN empresas emp ON c.id = emp.cliente_id
        WHERE e.id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evento_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $evento = $result->fetch_assoc();

            // Preparar los datos para la cotización
            $formData = [
                'encabezado' => !empty($evento['encabezado_evento']) ? $evento['encabezado_evento'] : ($evento['nombres'] . ' ' . $evento['apellidos']),
                'es_encabezado_evento' => !empty($evento['encabezado_evento']),
                'ciudad' => $evento['ciudad_evento'],
                'fecha' => $evento['fecha_evento'],
                'horario' => $evento['hora_evento'],
                'evento' => $evento['nombre_evento'],
                'valor' => $evento['valor_evento'],
                'hotel' => $evento['hotel'],
                'transporte' => $evento['traslados'],
                'viaticos' => $evento['viaticos'],
                // Agregar más campos según sea necesario
            ];

            // Crear y generar la cotización
            $quoteGenerator = new QuoteGenerator($formData, $config);
            $quoteGenerator->generate();
        } else {
            throw new Exception("No se encontró el evento especificado.");
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "Error: No se recibieron datos del formulario.";
    exit();
}
