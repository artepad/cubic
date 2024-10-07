<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

// Incluir archivos necesarios
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Shape;

/**
 * Clase ContractGenerator
 * 
 * Maneja la generación de contratos en formato Word
 */
class ContractGenerator
{
    private $conn;
    private $phpWord;
    private $section;
    private $eventData;

    /**
     * Constructor de la clase
     * 
     * @param mysqli $conn Conexión a la base de datos
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->phpWord = new PhpWord();
        $this->setupDocument();
    }

    /**
     * Configura el documento Word
     */
    private function setupDocument()
    {
        $this->phpWord->setDefaultFontName('Lato Light');
        $this->phpWord->setDefaultFontSize(10);
        $this->phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('ES_ES'));

        $sectionStyle = [
            'marginLeft' => 800,
            'marginRight' => 800,
            'marginTop' => 800,
            'marginBottom' => 800
        ];
        $this->section = $this->phpWord->addSection($sectionStyle);
    }

     /**
     * Obtiene los datos del evento de la base de datos y los convierte a mayúsculas
     * 
     * @param int $eventoId ID del evento
     * @throws Exception Si no se encuentran datos del evento
     */
    public function getEventData($eventoId)
    {
        $sql = "SELECT e.*, c.nombres, c.apellidos, c.rut, c.correo, c.celular, c.genero, 
                em.nombre as nombre_empresa, em.rut as rut_empresa, em.direccion as direccion_empresa
                FROM eventos e
                LEFT JOIN clientes c ON e.cliente_id = c.id
                LEFT JOIN empresas em ON c.id = em.cliente_id
                WHERE e.id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("i", $eventoId);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("No se encontraron datos del evento.");
        }

        $this->eventData = $result->fetch_assoc();

        // Convertir campos relevantes a mayúsculas
        $fieldsToUppercase = ['nombres', 'apellidos', 'rut', 'correo', 'celular', 'nombre_empresa', 'rut_empresa', 'direccion_empresa', 'lugar_evento', 'nombre_evento'];
        foreach ($fieldsToUppercase as $field) {
            if (isset($this->eventData[$field])) {
                $this->eventData[$field] = mb_strtoupper($this->eventData[$field], 'UTF-8');
            }
        }
    }

    /**
     * Genera el contenido del contrato
     */
    public function generateContract()
    {
        $this->addLogo();
        $this->addTitle();
        $this->addIntroduction();
        $this->addClauses();
        $this->addSignatures();
    }

    /**
     * Añade el logo al documento
     */
    private function addLogo()
    {
        $imagePath = __DIR__ . '/assets/img/logo-negro.png';
        if (!file_exists($imagePath)) {
            throw new Exception("La imagen no se encuentra en la ruta especificada: $imagePath");
        }

        $imageStyle = [
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
        ];
        $this->section->addImage($imagePath, $imageStyle);
    }

    /**
     * Añade el título al documento
     */
    private function addTitle()
    {
        $titleFontStyle = ['name' => 'Lato Light', 'size' => 14, 'bold' => true, 'color' => '1F4E79'];
        $titleParagraphStyle = ['alignment' => 'center', 'spaceAfter' => 500];
        $this->section->addText("CONTRATO DE ACTUACION DE ARTISTAS", $titleFontStyle, $titleParagraphStyle);
       
        // Añadir saltos de línea después del párrafo
        $this->addMultipleLineBreaks(1);
    }

    /**
     * Añade la introducción al contrato
     */
    private function addIntroduction()
    {
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $textRun->addText("Entre la ", null);
        $textRun->addText("PRODUCTORA", $boldStyle);
        $textRun->addText(" de eventos artísticos y representante legal de este, la señorita ", null);
        $textRun->addText("OLGA XIMENA SCHAAF GODOY", $boldStyle);
        $textRun->addText(", Rut: ", null);
        $textRun->addText("11.704.321-5", $boldStyle);
        $textRun->addText(", con domicilio: El Castaño N°01976, Alto del Maitén, Provincia de Melipilla, Región Metropolitana, en adelante denominada ", null);
        $textRun->addText("SCHAAFPRODUCCIONES SpA", ['bold' => true, 'allCaps' => true]);
        $textRun->addText(", Rut: ", null);
        $textRun->addText("76.748.346-5", $boldStyle);
        $textRun->addText(" por una parte, y por la otra ", null);
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" Rut: ", null);
        $textRun->addText("{$this->eventData['rut']}", $boldStyle);
        $textRun->addText(", en adelante, en representación de ", null);
        $textRun->addText("{$this->eventData['nombre_empresa']}", ['bold' => true, 'allCaps' => true]);
        $textRun->addText(", Rol Único Tributario Rut: ", null);
        $textRun->addText("{$this->eventData['rut_empresa']}", $boldStyle);
        $textRun->addText(" con domicilio en: ", null);
        $textRun->addText("{$this->eventData['direccion_empresa']}", $boldStyle);
        $textRun->addText(", se conviene en celebrar el presente contrato de actuación de artistas, contenido en las cláusulas siguientes:");

        // Añadir saltos de línea después del párrafo
        $this->addMultipleLineBreaks(1);
    }

     /**
     * Añade las cláusulas al contrato
     */
    private function addClauses()
    {
        $this->addClause1();
        $this->addClause2();
        $this->addClause3();
        $this->addClause4();
        $this->addClause5();
        $this->addPageBreak();
        $this->addClause6();
        $this->addClause7();
    }
    
    /**
    * Añade un salto de página al documento
    */
    private function addPageBreak()
    {
        $this->section->addPageBreak();
    }

    /**
     * Añade la Cláusula 1: Objeto del contrato
     */
    private function addClause1()
    {
        $this->section->addText("Cláusula 1: OBJETO DEL CONTRATO", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" CONTRATA LOS SERVICIOS DEL SIGUIENTE ARTISTA: ");
        $textRun->addText("AGRUPACIÓN MARILYN", $boldStyle);
        $textRun->addText(" EL MENCIONADO, EN ADELANTE Y A LOS EFECTOS DEL PRESENTE CONTRATO DENOMINADO, EL ARTISTA, EFECTUARÁ UNA (1) PRESENTACIÓN DE APROXIMADAMENTE 60 MINUTOS, A REALIZARSE EN EL MARCO DE PRESENTACIÓN PÚBLICA, EL DÍA ");
        $textRun->addText($this->convertirFecha($this->eventData['fecha_evento']), $boldStyle);
        $textRun->addText(" A LAS ");
        $textRun->addText(mb_strtoupper(date('H:i', strtotime($this->eventData['hora_evento'])), 'UTF-8'), $boldStyle);
        $textRun->addText(" EN ");
        $textRun->addText($this->eventData['lugar_evento'], $boldStyle);
        $textRun->addText(" PARA EL EVENTO ");
        $textRun->addText($this->eventData['nombre_evento'], $boldStyle);
    }

    /**
     * Añade la Cláusula 2: Remuneración
     */
    private function addClause2()
    {
        $this->section->addText("Cláusula 2: REMUNERACIÓN", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $valor_evento = intval($this->eventData['valor_evento']);
        $valor_en_palabras = $this->numberToWords($valor_evento);
        $valor_formateado = number_format($valor_evento, 0, ',', '.');

        $textRun->addText(" 2.1 Por la presentación mencionada en la Cláusula 1, ");
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" Pagará a ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" la cantidad de ");
        $textRun->addText("$" . $valor_formateado . " (" . $valor_en_palabras . " PESOS)", $boldStyle);

        $mitad_valor = $valor_evento / 2;
        $mitad_valor_palabras = $this->numberToWords($mitad_valor);
        $mitad_valor_formateado = number_format($mitad_valor, 0, ',', '.');

        $textRun->addText(" La cantidad de ");
        $textRun->addText("$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " PESOS)", $boldStyle);
        $textRun->addText(", correspondiente en parte a un 50% que será pagado a la firma del presente contrato en dinero en efectivo y solo moneda nacional o transferencia Bancaria.");
        $textRun->addText(" La cantidad de ");
        $textRun->addText("$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " PESOS)", $boldStyle);
        $textRun->addText(", correspondiente al 50% restante, por concepto de término de honorarios, deberá ser cancelado antes de subir al escenario el día ");
        $textRun->addText($this->convertirFecha($this->eventData['fecha_evento']), $boldStyle);
        $textRun->addText(", dinero en efectivo o transferencia Bancaria.");
        $textRun->addText(" BANCO SANTANDER, CUENTA CORRIENTE N° 71760359, RUT:76.748.346-5, NOMBRE: SCHAAFPRODUCCIONES SPA, CORREO: SCHAAFPRODUCCIONES@GMAIL.COM", $boldStyle);
    }
    /**
     * Añade la Cláusula 3: Alojamiento, traslados y viáticos
     */
    private function addClause3()
    {
        $this->section->addText("Cláusula 3: ALOJAMIENTO, TRASLADOS Y VIÁTICOS", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $servicios_productora = [];
        $servicios_cliente = [];

        if ($this->eventData['hotel'] == 'Si') $servicios_productora[] = 'alojamiento';
        else $servicios_cliente[] = 'alojamiento';

        if ($this->eventData['traslados'] == 'Si') $servicios_productora[] = 'traslados (ida y vuelta)';
        else $servicios_cliente[] = 'traslados (ida y vuelta)';

        if ($this->eventData['viaticos'] == 'Si') $servicios_productora[] = 'viáticos';
        else $servicios_cliente[] = 'viáticos';

        if (count($servicios_productora) == 3) {
            $textRun->addText("La responsabilidad de alojamiento, traslados (ida y vuelta) y viáticos estará a cargo de ");
            $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
            $textRun->addText(". El pago de los servicios de sonido y catering se hará cargo ");
            $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
            $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
        } elseif (count($servicios_cliente) == 3) {
            $textRun->addText("La responsabilidad de alojamiento, traslados (ida y vuelta) y viáticos estará a cargo de ");
            $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
            $textRun->addText(". El pago de los servicios de sonido y catering también será responsabilidad de ");
            $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
            $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
        } else {
            $servicios_productora_str = implode(', ', $servicios_productora);
            $servicios_cliente_str = implode(', ', $servicios_cliente);
            
            $textRun->addText("La responsabilidad de $servicios_productora_str estará a cargo de ");
            $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
            $textRun->addText(". La responsabilidad de $servicios_cliente_str y el pago de los servicios de sonido y catering estará a cargo de ");
            $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
            $textRun->addText(" el día de la presentación mencionada en la cláusula 1. ");
        }
    }

    /**
     * Añade la Cláusula 4: Suspensión
     */
    private function addClause4()
    {
        $this->section->addText("Cláusula 4: SUSPENSIÓN", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $textRun->addText("4.1 Salvo acuerdo entre ambas partes, ");
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" no podrá rescindir el presente contrato unilateralmente.");
        $textRun->addText(" Pero podrá solicitar la suspensión de la actuación del artista solamente con las siguientes causales:");
        $textRun->addText(" 4.2 Si ");
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" cancelara unilateralmente la presentación deberá pagar a ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" el 100% (cien por ciento) del monto establecido como Remuneración en la Cláusula 2 de este contrato por concepto de indemnización, haciéndose cargo también de los gastos en que ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" haya incurrido producto de la presentación que fuese cancelada.");
    }

    /**
     * Añade la Cláusula 5: Promoción
     */
    private function addClause5()
    {
        $this->section->addText("Cláusula 5: PROMOCIÓN", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" autoriza expresamente a ");
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" para utilizar el nombre del artista, biografía e imagen en la comunicación relativa a la promoción del espectáculo, pero en ningún caso ");
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" quedará facultad" . (strtoupper($this->eventData['genero']) == 'FEMENINO' ? "a" : "o") . " para relacionar directa o indirectamente la imagen del artista con marcas comerciales que puedan auspiciar el espectáculo. En caso de divergencias respecto a la forma en que la imagen del artista es utilizada, primará la opinión de ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(".");
    }

    /**
     * Añade la Cláusula 6: Seguridad
     */
    private function addClause6()
    {
        $this->section->addText("Cláusula 6: SEGURIDAD", ['bold' => true], ['spaceAfter' => 100, 'spaceBefore' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $boldStyle = ['bold' => true];

        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);
        $textRun->addText(" tiene la obligación de proveer la adecuada seguridad para el artista, los miembros de la delegación y el público que asiste al espectáculo.");
        $textRun->addText(" El acceso a camarines estará restringido exclusivamente a las personas que ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" identifique con sus propias identificaciones y debidamente custodiado por guardias profesionales.");
        $textRun->addText(" El escenario deberá tener barreras de contención adecuadas para mantener al público a una distancia no menor a los dos metros del borde del mismo, y deberán ubicarse guardias en el pasillo de seguridad entre el escenario y las barreras.");
        $textRun->addText(" Ningún guardia puede estar armado, aun teniendo los respectivos permisos que lo autoricen a ello. ");
        $textRun->addText("SCHAAFPRODUCCIONES SPA", $boldStyle);
        $textRun->addText(" tendrá la facultad de remover del lugar al personal de seguridad que estime, bajo su solo criterio, que no resulta adecuado para las funciones que debe cumplir.");
        
        // Añadir saltos de línea después del párrafo
        $this->addMultipleLineBreaks(1);
    }

    /**
     * Añade la información de coordinación
     */
    private function addClause7()
    {
        $this->section->addText("Cláusula 7: COORDINACIÓN", ['bold' => true], ['spaceAfter' => 100]);
        $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
        $textRun->addText("PARA LOS EFECTOS DE LA REALIZACIÓN DEL EVENTO, LAS PARTES DESIGNAN A LAS SIGUIENTES PERSONAS CON SUS RESPECTIVOS DATOS:");

        $this->section->addTextBreak();

        $leftAlignedStyle = ['alignment' => 'left'];
        $boldStyle = ['bold' => true];
        $this->section->addText("SCHAAFPRODUCCIONES SPA", $boldStyle, $leftAlignedStyle);
        $this->section->addText("SEÑORITA:   OLGA XIMENA SCHAAF GODOY", $boldStyle, $leftAlignedStyle);
        $this->section->addText("CELULAR:    +569995699801", $boldStyle, $leftAlignedStyle);
        $this->section->addText("CORREO:     ARTISTAS@SCHAAFPRODUCCIONES.CL", $boldStyle, $leftAlignedStyle);

        $tratamiento = (strtoupper($this->eventData['genero']) == 'FEMENINO') ? "SEÑORA:" : "SEÑOR:";

        $textRun = $this->section->addTextRun($leftAlignedStyle);
        $textRun->addText($tratamiento . "       ", $boldStyle, $leftAlignedStyle);
        $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);

        $textRun = $this->section->addTextRun($leftAlignedStyle);
        $textRun->addText("CELULAR:     ", $boldStyle, $leftAlignedStyle);
        $textRun->addText($this->eventData['celular'], $boldStyle);

        $textRun = $this->section->addTextRun($leftAlignedStyle);
        $textRun->addText("CORREO:     ", $boldStyle, $leftAlignedStyle);
        $textRun->addText($this->eventData['correo'], $boldStyle);

        // Añadir saltos de línea después del párrafo
        $this->addMultipleLineBreaks(5);
    }

     /**
     * Añade múltiples saltos de línea al documento
     *
     * @param int $count Número de saltos de línea a añadir
     */
    private function addMultipleLineBreaks($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->section->addTextBreak();
        }
    }

    /**
     * Añade las firmas al final del documento
     */
    private function addSignatures()
    {
        for ($i = 0; $i < 1; $i++) {
            $this->section->addTextBreak();
        }

        $table = $this->section->addTable();
        $table->addRow();
        $cell1 = $table->addCell(6000);
        $cell2 = $table->addCell(3000);

        $firmaPath = __DIR__ . '/assets/img/firma.png';
        if (!file_exists($firmaPath)) {
            throw new Exception("La imagen de la firma no se encuentra en la ruta especificada: $firmaPath");
        }
        $firmaStyle = [
            'width' => 100,
            'height' => 74,
            'alignment' => 'center',
            'marginBottom' => 5
        ];
        $cell1->addImage($firmaPath, $firmaStyle);

       
        $boldStyle = ['bold' => true];
        $cell1->addText("___________________________", $boldStyle, ['alignment' => 'center']);
        $cell1->addText("OLGA XIMENA SCHAAF", $boldStyle, ['alignment' => 'center']);
        $cell1->addText("76.748.346-5", $boldStyle, ['alignment' => 'center']);
        $cell1->addText("SCHAAFPRODUCCIONES SpA", $boldStyle, ['alignment' => 'center']);

        $firma2Path = __DIR__ . '/assets/img/firma-2.png';
        $cell2->addImage($firma2Path, $firmaStyle);

        $cell2->addText("___________________________", $boldStyle, ['alignment' => 'center']);
        $cell2->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle, ['alignment' => 'center']);
        $cell2->addText($this->eventData['rut_empresa'], $boldStyle, ['alignment' => 'center']);
        $cell2->addText($this->eventData['nombre_empresa'], $boldStyle, ['alignment' => 'center']);
    }

    /**
     * Guarda el documento generado
     * 
     * @param string $clienteNombre Nombre del cliente para el nombre del archivo
     */
    public function saveDocument($clienteNombre)
    {
        $fileName = "Contrato_" . preg_replace('/[^a-zA-Z0-9_]/', '', $clienteNombre) . ".docx";

        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        header("Content-Transfer-Encoding: binary");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");

        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save('php://output');
    }

    /**
     * Convierte un número a palabras
     * 
     * @param int $number Número a convertir
     * @return string Número en palabras
     */
    private function numberToWords($number)
    {
        $units = ["", "un", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve"];
        $tens = ["", "diez", "veinte", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"];
        $teens = ["diez", "once", "doce", "trece", "catorce", "quince", "dieciséis", "diecisiete", "dieciocho", "diecinueve"];
        $hundreds = ["", "ciento", "doscientos", "trescientos", "cuatrocientos", "quinientos", "seiscientos", "setecientos", "ochocientos", "novecientos"];

        if ($number == 0) {
            return "cero";
        }

        if ($number < 0) {
            return "menos " . $this->numberToWords(abs($number));
        }

        $words = [];

        if (($millones = floor($number / 1000000)) > 0) {
            $words[] = $millones == 1 ? "un millón" : $this->numberToWords($millones) . " millones";
            $number %= 1000000;
        }

        if (($miles = floor($number / 1000)) > 0) {
            $words[] = $miles == 1 ? "mil" : $this->numberToWords($miles) . " mil";
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

        return mb_strtoupper($result, 'UTF-8');
    }

    /**
     * Convierte una fecha a formato largo
     * 
     * @param string $fecha Fecha en formato Y-m-d
     * @return string Fecha en formato largo
     */
    private function convertirFecha($fecha)
    {
        $meses = array("ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE");
        $fecha = new DateTime($fecha);
        $dia = $fecha->format('j');
        $mes = $meses[$fecha->format('n') - 1];
        $anio = $fecha->format('Y');
        return mb_strtoupper("$dia DE $mes DEL $anio", 'UTF-8');
    }
}

// Uso de la clase
try {
    // Obtener el ID del evento y el nombre del cliente
    $evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $cliente_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';

    if ($evento_id <= 0) {
        throw new Exception("ID de evento no válido");
    }

    // Crear instancia de ContractGenerator
    $contractGenerator = new ContractGenerator($conn);
    // Obtener datos del evento
    $contractGenerator->getEventData($evento_id);

    // Generar el contrato
    $contractGenerator->generateContract();

    // Guardar el documento
    $contractGenerator->saveDocument($cliente_nombre);

} catch (Exception $e) {
    error_log("Error en generar_contrato.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    exit();
}