<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Image;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $encabezado = $_POST['encabezado'] ?? '';
    // Extraer otros campos del formulario aquí...

    $phpWord = new PhpWord();
    $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ES_ES));

    // Primera página (portada)
    $section1 = $phpWord->addSection([
        'orientation' => 'portrait',
        'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
        'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(11),
        'marginTop' => 0,
        'marginLeft' => 0,
        'marginRight' => 0,
        'marginBottom' => 0
    ]);

    $backgroundImagePath = 'assets/img/portada9.png';
    if (!file_exists($backgroundImagePath)) {
        die("Error: La imagen de fondo no se encuentra en la ruta especificada.");
    }

    $section1->addImage(
        $backgroundImagePath,
        [
            'width' => 612,
            'height' => 792,
            'positioning' => Image::POSITION_ABSOLUTE,
            'posHorizontal' => Image::POSITION_HORIZONTAL_CENTER,
            'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
            'posVertical' => Image::POSITION_VERTICAL_TOP,
            'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
            'wrappingStyle' => 'infront'
        ]
    );

    // Segunda página
    $section2 = $phpWord->addSection([
        'orientation' => 'portrait',
        'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
        'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(11),
        'marginTop' => 800,
        'marginLeft' => 800,
        'marginRight' => 800,
        'marginBottom' => 800
    ]);

    $phpWord->addFontStyle('titleStyle', [
        'name' => 'Lato',
        'size' => 23,
        'color' => '1F4E79',
        'bold' => true
    ]);

    $phpWord->addFontStyle('subtitleStyle', [
        'name' => 'Lato Light',
        'size' => 20,
        'bold' => true
    ]);

    $phpWord->addFontStyle('paragraphStyle', [
        'name' => 'Lato Light',
        'size' => 20,
        'bold' => false
    ]);

    $section2->addText('COTIZACIÓN ARTÍSTICA', 'titleStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
    ]);

    $section2->addTextBreak(1);

    $section2->addText(htmlspecialchars($encabezado), 'subtitleStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
    ]);

    $section2->addTextBreak(1);

    $paragraph = "Agradecemos desde ya su interés en el espectáculo de la reconocida banda Argentina, Agrupación Marilyn. Sin duda, esta banda representa una experiencia musical integral con una destacada trayectoria. Agrupación Marilyn ha conseguido un lugar especial en el corazón de seguidores tanto a nivel nacional como internacional. Su música, definida por la cumbia romántica y testimonial, narra historias que reflejan el cotidiano vivir con las cuales todos podemos identificarnos. Entre sus éxitos destacan Su florcita, Me enamoré, Te falta sufrir y Madre soltera. Actualmente, Agrupación Marilyn trabaja en su sexto disco, del cual ya han lanzado los exitosos singles: Abismo, Siento y Piel y Huesos, que adelantan una propuesta fresca y poderosa, fiel a su estilo.";

    $section2->addText($paragraph, 'paragraphStyle', [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH  
    ]);
  
    $section2->addTextBreak(1);
    // Añadir imagen al pie de la segunda página
    $imagePath = 'assets/img/agrupacionmarilyn3.png';
    if (!file_exists($imagePath)) {
        die("Error: La imagen no se encuentra en la ruta especificada.");
    }

    list($width, $height) = getimagesize($imagePath);
    $aspectRatio = $width / $height;
    $maxWidth = 400;
    $newHeight = $maxWidth / $aspectRatio;

    // Calcular el espacio restante en la página
    $pageHeight = $section2->getStyle()->getPageSizeH() - $section2->getStyle()->getMarginBottom() - $section2->getStyle()->getMarginTop();
    $usedSpace = $section2->getElemetnsCount() * 20; // Estimación aproximada del espacio usado
    $availableSpace = $pageHeight - $usedSpace;

    if ($newHeight > $availableSpace) {
        $newHeight = $availableSpace;
        $maxWidth = $newHeight * $aspectRatio;
    }

    $section2->addImage(
        $imagePath,
        [
            'width' => $maxWidth,
            'height' => $newHeight,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START,  // Cambiado de CENTER a START
            'positioning' => \PhpOffice\PhpWord\Style\Image::POSITION_RELATIVE,
            'wrappingStyle' => 'inline',
            'marginTop' => 0,
            'marginBottom' => 0,
            'marginLeft' => -50  // Valor negativo para mover la imagen a la izquierda
        ]
    );

    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=cotizacion_artistica.docx");
    header("Cache-Control: max-age=0");
    $writer->save("php://output");
    exit();
} else {
    echo "Error: No se recibieron datos del formulario.";
    exit();
}
?>