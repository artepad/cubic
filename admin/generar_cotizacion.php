<?php
// Asegúrate de que los errores se muestren durante el desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluye el autoloader de Composer
require_once 'vendor/autoload.php';

// Usa el espacio de nombres de PHPWord
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Crea una nueva instancia de PHPWord
$phpWord = new PhpWord();

// Añade una sección en blanco al documento
$section = $phpWord->addSection();

// Puedes añadir un párrafo en blanco si lo deseas
// $section->addText('');

// Crea el escritor de Word
$writer = IOFactory::createWriter($phpWord, 'Word2007');

// Prepara la respuesta HTTP
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Disposition: attachment; filename=documento_en_blanco.docx");
header("Cache-Control: max-age=0");

// Guarda el documento
$writer->save("php://output");
exit();
?>