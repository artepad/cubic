<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Image;

$phpWord = new PhpWord();

$backgroundImagePath = 'assets/img/portada2.jpg';
$logoImagePath = 'assets/img/logocolores.png';

if (!file_exists($backgroundImagePath) || !file_exists($logoImagePath)) {
    die("Error: Una o ambas imágenes no se encuentran en la ruta especificada.");
}

$section = $phpWord->addSection();

// Dimensiones de la página
$pageWidthPx = \PhpOffice\PhpWord\Shared\Converter::inchToPixel(8.5);
$pageHeightPx = \PhpOffice\PhpWord\Shared\Converter::inchToPixel(11);

// Imagen de fondo
$backgroundWidth = 720;
$backgroundHeight = 960;
$backgroundLeftOffset = ($pageWidthPx - $backgroundWidth) / 2;
$backgroundTopOffset = ($pageHeightPx - $backgroundHeight) / 2;

$section->addImage(
    $backgroundImagePath,
    array(
        'width' => $backgroundWidth,
        'height' => $backgroundHeight,
        'positioning' => Image::POSITION_ABSOLUTE,
        'posHorizontal' => Image::POSITION_HORIZONTAL_LEFT,
        'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
        'posVertical' => Image::POSITION_VERTICAL_TOP,
        'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
        'marginLeft' => $backgroundLeftOffset,
        'marginTop' => $backgroundTopOffset,
        'wrappingStyle' => 'behind'
    )
);

// Logo con coordenadas ajustables
$logoWidth = 500; // Ancho en píxeles
$logoHeight = 281; // Alto en píxeles

// Coordenadas para el logo (ajusta estos valores según necesites)
$logoX = 300; // Distancia desde el borde izquierdo de la página
$logoY = 800; // Distancia desde el borde superior de la página

// Crear un TextRun y añadir la imagen a él
$textrun = $section->addTextRun();
$textrun->addImage(
    $logoImagePath,
    array(
        'width' => $logoWidth,
        'height' => $logoHeight,
        'positioning' => Image::POSITION_ABSOLUTE,
        'posHorizontal' => Image::POSITION_HORIZONTAL_LEFT,
        'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
        'posVertical' => Image::POSITION_VERTICAL_TOP,
        'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
        'marginLeft' => $logoX,
        'marginTop' => $logoY,
        'wrappingStyle' => 'infront'
    )
);

$writer = IOFactory::createWriter($phpWord, 'Word2007');

header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Disposition: attachment; filename=documento_con_logo_movil.docx");
header("Cache-Control: max-age=0");

$writer->save("php://output");
exit();
?>