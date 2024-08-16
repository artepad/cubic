<?php
// Habilitar el reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

error_log("POST data: " . print_r($_POST, true));

// Libreria Word
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Shape;

// Función para convertir números a palabras
function numberToWords($number)
{
    $units = ["", "un", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve"];
    $tens = ["", "diez", "veinte", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"];
    $teens = ["diez", "once", "doce", "trece", "catorce", "quince", "dieciséis", "diecisiete", "dieciocho", "diecinueve"];
    $hundreds = ["", "ciento", "doscientos", "trescientos", "cuatrocientos", "quinientos", "seiscientos", "setecientos", "ochocientos", "novecientos"];

    if ($number == 0) {
        return "cero";
    }

    if ($number < 0) {
        return "menos " . numberToWords(abs($number));
    }

    $words = [];

    if (($millones = floor($number / 1000000)) > 0) {
        $words[] = $millones == 1 ? "un millón" : numberToWords($millones) . " millones";
        $number %= 1000000;
    }

    if (($miles = floor($number / 1000)) > 0) {
        $words[] = $miles == 1 ? "mil" : numberToWords($miles) . " mil";
        $number %= 1000;
    }

    if (($centenas = floor($number / 100)) > 0) {
        if ($centenas == 1 && $number % 100 == 0) {
            $words[] = "cien";
        } else {
            $words[] = $hundreds[$centenas];
        }
        $number %= 100;
    }

    if ($number >= 20) {
        $words[] = $tens[floor($number / 10)];
        if ($number % 10 > 0) {
            $words[] = "y " . $units[$number % 10];
        }
    } elseif ($number >= 10) {
        $words[] = $teens[$number - 10];
    } elseif ($number > 0) {
        $words[] = $units[$number];
    }

    $result = implode(" ", $words);

    return $result;
}

function mb_strtoupper_custom($string)
{
    $string = str_replace(
        array('á', 'é', 'í', 'ó', 'ú', 'ñ'),
        array('Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'),
        mb_strtoupper($string, 'UTF-8')
    );
    return $string;
}

function cleanString($string)
{
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schaaf_producciones";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    error_log("Conexión fallida: " . $conn->connect_error);
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener los datos del cliente y del evento
$cliente_id = $_POST['cliente_id'];
$evento_id = $_POST['evento_id'];
$hotel = $_POST['hotel'];
$traslados = $_POST['traslados'];
$viaticos = $_POST['viaticos'];
$valor_evento = isset($_POST['valor']) ? intval($_POST['valor']) : 0;

error_log("Cliente ID: $cliente_id");
error_log("Evento ID: $evento_id");
error_log("Valor del evento (desde POST): $valor_evento");

// Verificar si se está creando un nuevo evento o usando uno existente
if ($_POST['evento_id'] == '0') {
    $sql_insert_evento = "INSERT INTO eventos (cliente_id, nombre_evento, fecha_evento, hora_evento, lugar, valor, tipo_evento, hotel, traslados, viaticos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert_evento);
    $stmt_insert->bind_param("issssissss", $cliente_id, $_POST['nombre_evento'], $_POST['fecha_evento'], $_POST['hora_evento'], $_POST['lugar'], $valor_evento, $_POST['tipo_evento'], $hotel, $traslados, $viaticos);

    if (!$stmt_insert->execute()) {
        error_log("Error al insertar nuevo evento: " . $stmt_insert->error);
        error_log("Valor que se intentó insertar: " . $valor_evento);
        die("Error al crear el nuevo evento.");
    }

    $evento_id = $conn->insert_id;
} else {
    $sql_update_evento = "UPDATE eventos SET nombre_evento = ?, fecha_evento = ?, hora_evento = ?, lugar = ?, valor = ?, tipo_evento = ?, hotel = ?, traslados = ?, viaticos = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update_evento);
    $stmt_update->bind_param("ssssdssssi", $_POST['nombre_evento'], $_POST['fecha_evento'], $_POST['hora_evento'], $_POST['lugar'], $valor_evento, $_POST['tipo_evento'], $hotel, $traslados, $viaticos, $_POST['evento_id']);

    if (!$stmt_update->execute()) {
        error_log("Error al actualizar evento: " . $stmt_update->error);
        error_log("Valor que se intentó actualizar: " . $valor_evento);
        die("Error al actualizar el evento.");
    }

    $evento_id = $_POST['evento_id'];
}

// Consulta para obtener los datos del cliente y del evento
$sql = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa, 
               ev.nombre_evento, ev.fecha_evento, ev.hora_evento, ev.lugar, ev.valor, ev.tipo_evento
        FROM clientes c
        LEFT JOIN empresas e ON c.id = e.cliente_id
        LEFT JOIN eventos ev ON c.id = ev.cliente_id
        WHERE c.id = ? AND ev.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cliente_id, $evento_id);

if (!$stmt->execute()) {
    error_log("Error en la ejecución de la consulta: " . $stmt->error);
    die("Error al obtener los datos del cliente y evento.");
}

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    error_log("No se encontraron datos del cliente o del evento. cliente_id: $cliente_id, evento_id: $evento_id");
    die("No se encontraron datos del cliente o del evento.");
}

$row = $result->fetch_assoc();
error_log("Datos recuperados: " . print_r($row, true));

// Verificar si el valor del evento es válido
if ($valor_evento === 0) {
    error_log("Error: El valor del evento es 0 o no se pudo obtener.");
    // Puedes decidir si quieres continuar o detener la ejecución aquí
}

// Crear un nuevo documento Word
$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Lato Light');
$phpWord->setDefaultFontSize(10);
$phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('ES_ES'));

// Establecer los márgenes
$sectionStyle = [
    'marginLeft' => 800,
    'marginRight' => 800,
    'marginTop' => 800,
    'marginBottom' => 800
];
$section = $phpWord->addSection($sectionStyle);

// Agregar la imagen de forma simple
$imagePath = 'img/logo-negro.png';
$imageStyle = array(
    'width' => 100,
    'height' => 49,
    'positioning' => 'absolute',
    'posHorizontal' => \PhpOffice\PhpWord\Style\Image::POSITION_HORIZONTAL_RIGHT,
    'posHorizontalRel' => 'page',
    'posVertical' => \PhpOffice\PhpWord\Style\Image::POSITION_VERTICAL_BOTTOM,
    'posVerticalRel' => 'page',
    'wrappingStyle' => 'behind',
    'marginRight' => 40,
    'marginBottom' => 20
);
$section->addImage($imagePath, $imageStyle);

// Añadir título con formato especificado
$titleFontStyle = ['name' => 'Lato Light', 'size' => 14, 'bold' => true, 'color' => '1F4E79'];
$titleParagraphStyle = ['alignment' => 'center', 'spaceAfter' => 500];
$section->addText("CONTRATO DE ACTUACION DE ARTISTAS", $titleFontStyle, $titleParagraphStyle);

// Convertir campos a mayúsculas
$nombres = mb_strtoupper_custom($row['nombres']);
$apellidos = mb_strtoupper_custom($row['apellidos']);
$nombres = htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8');
$apellidos = htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8');
$nombres = cleanString($nombres);
$apellidos = cleanString($apellidos);
$rut = mb_strtoupper_custom($row['rut']);
$correo = mb_strtoupper_custom($row['correo']);
$celular = mb_strtoupper_custom($row['celular']);
$genero = mb_strtoupper_custom($row['genero']);
$nombre_empresa = mb_strtoupper_custom($row['nombre_empresa']);
$rut_empresa = mb_strtoupper_custom($row['rut_empresa']);
$direccion_empresa = mb_strtoupper_custom($row['direccion_empresa']);
$lugar_evento = mb_strtoupper_custom($row['lugar']);
$fecha_evento = mb_strtoupper_custom($row['fecha_evento']);
$nombre_evento = mb_strtoupper_custom($row['nombre_evento']);
$nombre_agrupacion = "AGRUPACIÓN MARILYN";
$nombre_productora = "SCHAAFPRODUCCIONES SPA";

// Definir estilo para texto en negrita
$boldFontStyle = ['name' => 'Lato Light', 'size' => 10, 'bold' => true];

// Añadir contenido con partes en negrita
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("Entre la ", null);
$textRun->addText("PRODUCTORA", $boldFontStyle);
$textRun->addText(" de eventos artísticos y representante legal de este, la señorita ", null);
$textRun->addText("OLGA XIMENA SCHAAF GODOY", $boldFontStyle);
$textRun->addText(", Rut: ", null);
$textRun->addText("11.704.321-5", ['bold' => true]);
$textRun->addText(", con domicilio: El Castaño N°01976, Alto del Maitén, Provincia de Melipilla, Región Metropolitana, en adelante denominada ", null);
$textRun->addText("SCHAAFPRODUCCIONES SpA", ['bold' => true, 'allCaps' => true]);
$textRun->addText(", Rut: ", null);
$textRun->addText("76.748.346-5", ['bold' => true]);
$textRun->addText(" por una parte, y por la otra ", null);
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" Rut: ", null);
$textRun->addText("$rut", ['bold' => true]);
$textRun->addText(", en adelante, en representación de ", null);
$textRun->addText("$nombre_empresa", ['bold' => true, 'allCaps' => true]);
$textRun->addText(", Rol Único Tributario Rut: ", null);
$textRun->addText("$rut_empresa", ['bold' => true]);
$textRun->addText(" con domicilio en: ", null);
$textRun->addText("$direccion_empresa", $boldFontStyle);
$textRun->addText(", se conviene en celebrar el presente contrato de actuación de artistas, contenido en las cláusulas siguientes:");

//$section->addTextBreak();$valor_evento = isset($_POST['valor']) ? intval($_POST['valor']) : 0;
$section->addText("REPRESENTATIVIDAD", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("La productora declara que se encuentra facultada para firmar contratos que comprometan actuaciones en vivo de los artistas ");
$textRun->addText($nombre_agrupacion, $boldFontStyle);
$textRun->addText(" objeto del presente Contrato en el territorio de Chile. Por su parte ");
$textRun->addText("SCHAAFPRODUCCIONES SPA", $boldFontStyle);
$textRun->addText(" declara ser una productora solvente, que cuenta con los recursos necesarios para realizar espectáculos de la envergadura de los compromisos que adquiere mediante este Contrato, que cuenta con las respectivas autorizaciones y que no mantiene situaciones de mora ni con el Fisco ni con terceros que puedan generar demandas que afecten la realización de espectáculos públicos con sus artistas.");
$section->addTextBreak();

$section->addText("Cláusula 1: OBJETO DEL CONTRATO", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" contrata los servicios del siguiente artista: ");
$textRun->addText($nombre_agrupacion, $boldFontStyle);
$textRun->addText(" el mencionado, en adelante y a los efectos del presente Contrato denominado, el artista, efectuará una (1) presentación de aproximadamente 60 minutos, a realizarse en el marco de presentación pública, el día ");
$textRun->addText("$fecha_evento", $boldFontStyle);
$textRun->addText(" a las ");
$textRun->addText("$row[hora_evento]", $boldFontStyle);
$textRun->addText(" en ");
$textRun->addText("$lugar_evento", $boldFontStyle);
$textRun->addText(" para el evento ");
$textRun->addText("$nombre_evento", $boldFontStyle);

// Cláusula 2: REMUNERACIÓN
$valor_en_palabras = numberToWords($valor_evento);
$valor_formateado = number_format($valor_evento, 0, ',', '.');

$textRun->addText(" 2.1 Por la presentación mencionada en la Cláusula 1, ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" Pagará a ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" la cantidad de ");
$textRun->addText("$" . $valor_formateado . " (" . $valor_en_palabras . " pesos)", $boldFontStyle);
$textRun->addText(" 2.2 La cantidad mencionada en el punto 2.1 será pagada por ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" en la siguiente forma:");

$mitad_valor = $valor_evento / 2;
$mitad_valor_palabras = numberToWords($mitad_valor);
$mitad_valor_formateado = number_format($mitad_valor, 0, ',', '.');

$textRun->addText(" La cantidad de ");
$textRun->addText("$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " pesos)", $boldFontStyle);
$textRun->addText(", correspondiente en parte a un 50% que será pagado a la firma del presente contrato en dinero en efectivo y solo moneda nacional o transferencia Bancaria.");
$textRun->addText(" La cantidad de ");
$textRun->addText("$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " pesos)", $boldFontStyle);
$textRun->addText(", correspondiente al 50% restante, por concepto de término de honorarios, deberá ser cancelado antes de subir al escenario el día ");
$textRun->addText("$fecha_evento", $boldFontStyle);
$textRun->addText(", dinero en efectivo o transferencia Bancaria. Cuenta corriente N° 71 76 03 59 Banco Santander, RUT:76.748.346-5.");
$textRun->addText("2.3 El no pago oportuno de las obligaciones, será causal suficiente para que ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" cancele la presentación del artista, considerándose esta circunstancia como una cancelación no justificada por parte del contratante y por lo tanto estará sujeto a la jurisdicción de tribunales en la ciudad de Santiago con la abogada ");
$textRun->addText("PAULA MOLINA MALLEA.", $boldFontStyle);


$section->addTextBreak();
$section->addText("Cláusula 3: ALOJAMIENTO, TRASLADOS Y VIÁTICOS", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$servicios_productora = [];
$servicios_cliente = [];

if ($hotel == 'Si') $servicios_productora[] = 'alojamiento';
else $servicios_cliente[] = 'alojamiento';

if ($traslados == 'Si') $servicios_productora[] = 'traslados (ida y vuelta)';
else $servicios_cliente[] = 'traslados (ida y vuelta)';

if ($viaticos == 'Si') $servicios_productora[] = 'viáticos';
else $servicios_cliente[] = 'viáticos';

// Generar el texto de la Cláusula 3
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);

if (count($servicios_productora) == 3) {
    $textRun->addText("La responsabilidad de alojamiento, traslados (ida y vuelta) y viáticos estará a cargo de ");
    $textRun->addText($nombre_productora, $boldFontStyle);
    $textRun->addText(". El pago de los servicios de sonido y catering se hará cargo ");
    $textRun->addText("$nombres $apellidos", $boldFontStyle);
    $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
} elseif (count($servicios_cliente) == 3) {
    $textRun->addText("La responsabilidad de alojamiento, traslados (ida y vuelta) y viáticos estará a cargo de ");
    $textRun->addText("$nombres $apellidos", $boldFontStyle);
    $textRun->addText(". El pago de los servicios de sonido y catering también será responsabilidad de ");
    $textRun->addText("$nombres $apellidos", $boldFontStyle);
    $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
} else {
    $servicios_productora_str = implode(', ', $servicios_productora);
    $servicios_cliente_str = implode(', ', $servicios_cliente);
    $textRun->addText("La responsabilidad de $servicios_productora_str estará a cargo de ");
    $textRun->addText($nombre_productora, $boldFontStyle);
    $textRun->addText(". La responsabilidad de $servicios_cliente_str y el pago de los servicios de sonido y catering estará a cargo de ");
    $textRun->addText("$nombres $apellidos", $boldFontStyle);
    $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
}
// Agregar salto de página antes de la Cláusula 4
$section->addTextBreak();
$section->addText("Cláusula 4: SUSPENSIÓN 1", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("4.1 Salvo acuerdo entre ambas partes, ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" no podrá rescindir el presente contrato unilateralmente.");
$textRun->addText(" Pero podrá solicitar la suspensión de la actuación del artista solamente con las siguientes causales:");
$textRun->addText(" 4.2 Si ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" cancelara unilateralmente la presentación deberá pagar a ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" el 100% (cien por ciento) del monto establecido como Remuneración en la Cláusula 2 de este contrato por concepto de indemnización, haciéndose cargo también de los gastos en que ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" haya incurrido producto de la presentación que fuese cancelada.");
// Agregar salto de página antes de la Cláusula 5
$section->addPageBreak();

$section->addText("Cláusula 5: PROMOCIÓN", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);

$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" autoriza expresamente a ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" para utilizar el nombre del artista, biografía e imagen en la comunicación relativa a la promoción del espectáculo, pero en ningún caso ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" quedará facultad" . (strtoupper($genero) == 'FEMENINO' ? "a" : "o") . " para relacionar directa o indirectamente la imagen del artista con marcas comerciales que puedan auspiciar el espectáculo. En caso de divergencias respecto a la forma en que la imagen del artista es utilizada, primará la opinión de ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(".");
$textRun->addText(" Por lluvias intensas, inundaciones o incendio que afecten al local de actuación, catástrofe nacional, terremoto u otra causa fortuita no controlable por ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(". Por dictamen de la autoridad gubernamental, Carabineros de Chile, Cesma, Bomberos, etc. que sea debidamente demostrada por escrito, siempre que dicha prohibición de la autoridad no sea causada por el incumplimiento de ");
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" de las normas que rigen la realización de espectáculos públicos. En este caso, ambas partes deberán acordar una nueva fecha para la presentación del artista.");
$section->addTextBreak();

$section->addText("Cláusula 6: SEGURIDAD", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("$nombres $apellidos", $boldFontStyle);
$textRun->addText(" tiene la obligación de proveer la adecuada seguridad para el artista, los miembros de la delegación y el público que asiste al espectáculo.");
$textRun->addText(" El acceso a camarines estará restringido exclusivamente a las personas que ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" identifique con sus propias identificaciones y debidamente custodiado por guardias profesionales.");
$textRun->addText(" El escenario deberá tener barreras de contención adecuadas para mantener al público a una distancia no menor a los dos metros del borde del mismo, y deberán ubicarse guardias en el pasillo de seguridad entre el escenario y las barreras.");
$textRun->addText(" Ningún guardia puede estar armado, aun teniendo los respectivos permisos que lo autoricen a ello. ");
$textRun->addText($nombre_productora, $boldFontStyle);
$textRun->addText(" tendrá la facultad de remover del lugar al personal de seguridad que estime, bajo su solo criterio, que no resulta adecuado para las funciones que debe cumplir.");
$section->addTextBreak();

// Cláusula 7
$section->addText("Cláusula 7: COORDINACIÓN", ['name' => 'Lato Light', 'size' => 10, 'bold' => true], ['spaceAfter' => 100]);
$textRun = $section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
$textRun->addText("Para los efectos de la realización del evento, las partes designan a las siguientes personas con sus respectivos datos:");

$section->addTextBreak();

// Información de contacto alineada a la izquierda
$leftAlignedStyle = ['alignment' => 'left'];
$boldStyle = ['bold' => true];
$section->addText("SCHAAFPRODUCCIONES SPA", $boldStyle, $leftAlignedStyle);
$section->addText("Señorita:   OLGA XIMENA SCHAAF GODOY", $boldStyle, $leftAlignedStyle);
$section->addText("Celular:    +569995699801", $boldStyle, $leftAlignedStyle);
$section->addText("Correo:     ARTISTAS@SCHAAFPRODUCCIONES.CL", $boldStyle, $leftAlignedStyle);

// Determinar el tratamiento basado en el género
$tratamiento = (strtoupper($genero) == 'FEMENINO') ? "Señora:" : "Señor:";

$textRun = $section->addTextRun($leftAlignedStyle);
$textRun->addText($tratamiento . "       ", $boldStyle, $leftAlignedStyle);
$textRun->addText("$nombres $apellidos", $boldFontStyle);

$textRun = $section->addTextRun($leftAlignedStyle);
$textRun->addText("Celular:     ", $boldStyle, $leftAlignedStyle);
$textRun->addText("$celular", $boldFontStyle);

$textRun = $section->addTextRun($leftAlignedStyle);
$textRun->addText("Correo:     ", $boldStyle, $leftAlignedStyle);
$textRun->addText("$correo", $boldFontStyle);

// Agregar espacio antes de las firmas
for ($i = 0; $i < 1; $i++) {
    $section->addTextBreak();
}

// Agregar firmas
$table = $section->addTable();
$table->addRow();
$cell1 = $table->addCell(6000);
$cell2 = $table->addCell(3000);

// Añadir la imagen de la firma en la primera celda
$firmaPath = 'img/firma.png';
$firmaStyle = array(
    'width' => 100,
    'height' => 74,
    'alignment' => 'center',
    'marginBottom' => 5
);
$cell1->addImage($firmaPath, $firmaStyle);

$cell1->addText("___________________________", $boldFontStyle, ['alignment' => 'center']);
$cell1->addText("OLGA XIMENA SCHAAF", $boldFontStyle, ['alignment' => 'center']);
$cell1->addText("76.748.346-5", $boldFontStyle, ['alignment' => 'center']);
$cell1->addText("SCHAAFPRODUCCIONES SpA", $boldFontStyle, ['alignment' => 'center']);

// Añadir la segunda imagen de firma en la segunda celda
$firma2Path = 'img/firma-2.png';
$cell2->addImage($firma2Path, $firmaStyle);

$cell2->addText("___________________________", $boldFontStyle, ['alignment' => 'center']);
$cell2->addText("$nombres $apellidos", $boldFontStyle, ['alignment' => 'center']);
$cell2->addText("$rut_empresa", $boldFontStyle, ['alignment' => 'center']);
$cell2->addText("$nombre_empresa", $boldFontStyle, ['alignment' => 'center']);

// Guardar el documento
$fileName = "Contrato_{$nombres}_{$apellidos}.docx";
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');

try {
    $objWriter->save($fileName);
    error_log("Documento guardado exitosamente: $fileName");
} catch (Exception $e) {
    error_log("Error al guardar el documento: " . $e->getMessage());
    die("Error al generar el contrato. Por favor, contacte al administrador.");
}

// Descargar el documento
$fileName = "Contrato_{$nombres}_{$apellidos}.docx";
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');

try {
    $objWriter->save($fileName);
    error_log("Documento guardado exitosamente: $fileName");
} catch (Exception $e) {
    error_log("Error al guardar el documento: " . $e->getMessage());
    die("Error al generar el contrato. Por favor, contacte al administrador.");
}

// Descargar el documento
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$fileName");
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Transfer-Encoding: binary");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

if (file_exists($fileName)) {
    readfile($fileName);
    unlink($fileName); // Eliminar el archivo después de enviarlo
} else {
    error_log("El archivo $fileName no existe.");
    die("Error al descargar el contrato. Por favor, contacte al administrador.");
}

$conn->close();
exit();
