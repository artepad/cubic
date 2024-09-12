<?php
// Habilitar la visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la librería PhpWord
require_once 'vendor/autoload.php';

// Importar las clases necesarias de PhpWord
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Image;

// Asegurarse de que se han enviado los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extraer los datos del formulario
    $encabezado = $_POST['encabezado'] ?? '';
    // Extraer otros campos del formulario aquí...

    // Crear una nueva instancia de PhpWord
    $phpWord = new PhpWord();

    // Configurar el idioma del documento a español
    $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ES_ES));

    // Configuración de la primera página (portada)
    $section1 = $phpWord->addSection([
        'orientation' => 'portrait',
        'marginTop' => 0,
        'marginLeft' => 0,
        'marginRight' => 0,
        'marginBottom' => 0
    ]);

    // Ruta de la nueva imagen de fondo
    $backgroundImagePath = 'assets/img/portada5.png';

    // Verificar si la imagen de fondo existe
    if (!file_exists($backgroundImagePath)) {
        die("Error: La imagen de fondo no se encuentra en la ruta especificada.");
    }

    // Dimensiones exactas de la imagen
    $backgroundWidth = 720;
    $backgroundHeight = 960;

    // Añadir la imagen de fondo a la sección
    $section1->addImage(
        $backgroundImagePath,
        array(
            'width' => $backgroundWidth,
            'height' => $backgroundHeight,
            'positioning' => Image::POSITION_ABSOLUTE,
            'posHorizontal' => Image::POSITION_HORIZONTAL_CENTER,
            'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
            'posVertical' => Image::POSITION_VERTICAL_TOP,
            'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
            'wrappingStyle' => 'infront'
        )
    );

    // Configuración de la segunda página
    $section2 = $phpWord->addSection([
        'orientation' => 'portrait',
        'marginTop' => 800,
        'marginLeft' => 800,
        'marginRight' => 800,
        'marginBottom' => 800
    ]);

    // Estilo para el título principal
    $phpWord->addFontStyle('titleStyle', [
        'name' => 'Lato Light',
        'size' => 23,
        'color' => '1F4E79',
        'bold' => true
    ]);

    // Estilo para el subtítulo
    $phpWord->addFontStyle('subtitleStyle', [
        'name' => 'Lato Light',
        'size' => 18,
        'bold' => true
    ]);

    // Añadir el título principal
    $section2->addText('COTIZACIÓN ARTÍSTICA', 'titleStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
    ]);

    // Añadir un espacio entre el título y el subtítulo
    $section2->addTextBreak(1);

    // Añadir el subtítulo (encabezado del formulario)
    $section2->addText(htmlspecialchars($encabezado), 'subtitleStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
    ]);

    // Estilo para el párrafo
    $phpWord->addFontStyle('paragraphStyle', [
        'name' => 'Lato Light',
        'size' => 18,
        'bold' => false
    ]);

    // Añadir un espacio entre el subtítulo y el párrafo
    $section2->addTextBreak(1);

    // Añadir el párrafo
    $paragraph = "Agradecemos desde ya su interés en el espectáculo de la reconocida banda Argentina, Agrupación Marilyn. Sin lugar a dudas, esta banda representa una experiencia musical integral con una destacada trayectoria en el ámbito musical. Agrupación Marilyn ha conseguido un lugar especial en el corazón de innumerables seguidores tanto a nivel nacional como internacional. Su música, que se define por el género de la cumbia romántica y testimonial, narra historias y relatos que son un reflejo del cotidiano vivir y con los cuales todos podemos identificarnos. Entre sus éxitos más destacados se encuentran temas como \"Su florcita\", \"Me enamoré\", \"Te falta sufrir\" y \"Madre soltera\", los cuales forman parte fundamental de sus vibrantes presentaciones en vivo. En la actualidad, Agrupación Marilyn se encuentra trabajando en su esperado sexto disco, del cual ya han lanzado los exitosos singles: \"Abismo\", \"Siento\" y \"Piel y Huesos\", que adelantan una propuesta fresca y poderosa, fiel al estilo que ha conquistado a su público.";

    $section2->addText($paragraph, 'paragraphStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH  
    ]);
    
    // Crear el escritor para generar el documento Word
    $writer = IOFactory::createWriter($phpWord, 'Word2007');

    // Configurar las cabeceras para la descarga del archivo
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=cotizacion_artistica.docx");
    header("Cache-Control: max-age=0");

    // Guardar el documento y enviarlo al navegador
    $writer->save("php://output");
    exit();
} else {
    // Manejar el caso en que no se hayan enviado datos del formulario
    echo "Error: No se recibieron datos del formulario.";
    exit();
}
?>