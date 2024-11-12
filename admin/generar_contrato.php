<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;

class ContractGenerator
{
    private $conn;
    private $phpWord;
    private $section;
    private $eventData;

    // Configuración de estilos del documento
    private const DOCUMENT_STYLES = [
        'default_font' => [
            'name' => 'Lato Light',
            'size' => 10
        ],
        'margins' => [
            'left' => 800,
            'right' => 800,
            'top' => 800,
            'bottom' => 800
        ]
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializePhpWord();
    }

    // Inicializa la configuración de PhpWord
    private function initializePhpWord()
    {
        Settings::setZipClass(ZipArchive::class);
        Settings::setOutputEscapingEnabled(true);

        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName(self::DOCUMENT_STYLES['default_font']['name']);
        $this->phpWord->setDefaultFontSize(self::DOCUMENT_STYLES['default_font']['size']);
        $this->phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('ES_ES'));

        $this->section = $this->phpWord->addSection(self::DOCUMENT_STYLES['margins']);
    }

    // Obtiene los datos del evento desde la base de datos
    public function getEventData($eventoId)
    {
        if (!is_numeric($eventoId) || $eventoId <= 0) {
            throw new InvalidArgumentException("ID de evento inválido");
        }

        $sql = "SELECT e.*, c.nombres, c.apellidos, c.rut, c.correo, c.celular, c.genero, 
                em.nombre as nombre_empresa, em.rut as rut_empresa, em.direccion as direccion_empresa
                FROM eventos e
                LEFT JOIN clientes c ON e.cliente_id = c.id
                LEFT JOIN empresas em ON c.id = em.cliente_id
                WHERE e.id = ?";

        try {
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
            return true;
        } catch (Exception $e) {
            error_log("Error en getEventData: " . $e->getMessage());
            throw $e;
        }
    }
    // Función principal para generar el contrato
    public function generateContract()
    {
        try {
            $this->addLogo();
            $this->addTitle();
            $this->addIntroduction();
            $this->addClauses();
            $this->addSignatures();
        } catch (Exception $e) {
            error_log("Error en generateContract: " . $e->getMessage());
            throw new Exception("Error al generar el contrato: " . $e->getMessage());
        }
    }
    // Función auxiliar para sanitizar el nombre del archivo
    private function sanitizeFileName($name)
    {
        // Convertir caracteres especiales a sus equivalentes sin acentos
        $name = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $name);
        // Eliminar caracteres no alfanuméricos y reemplazar espacios con guiones bajos
        $name = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $name = preg_replace('/\s+/', '_', trim($name));
        return $name;
    }
    // Función para guardar el documento
    public function saveDocument()
    {
        try {
            // Limpiar el buffer de salida
            if (ob_get_level()) ob_end_clean();

            // Obtener el nombre del cliente y sanitizarlo
            $clientName = $this->sanitizeFileName($this->eventData['nombres'] . ' ' . $this->eventData['apellidos']);

            // Generar nombre del archivo con el nombre del cliente
            $fileName = "Contrato_Evento_" . $clientName . ".docx";

            // Configurar headers para la descarga
            header("Content-Description: File Transfer");
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
            header("Content-Transfer-Encoding: binary");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Expires: 0");
            header("Pragma: public");

            // Guardar y enviar el archivo
            $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
            $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
            $objWriter->save($tempFile);

            readfile($tempFile);
            unlink($tempFile);
            exit();
        } catch (Exception $e) {
            error_log("Error en saveDocument: " . $e->getMessage());
            throw new Exception("Error al guardar el documento: " . $e->getMessage());
        }
    }

    // Añade el logo al documento
    private function addLogo()
    {
        $imagePath = __DIR__ . '/assets/img/logo-negro.png';
        if (!file_exists($imagePath)) {
            throw new Exception("Logo no encontrado en: $imagePath");
        }

        try {
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
        } catch (Exception $e) {
            throw new Exception("Error al añadir el logo: " . $e->getMessage());
        }
    }

    // Añade el título del contrato
    private function addTitle()
    {
        $titleStyle = [
            'font' => ['name' => 'Lato', 'size' => 15, 'bold' => true, 'color' => '1F4E79'],
            'paragraph' => ['alignment' => 'center', 'spaceAfter' => 500]
        ];

        try {
            $this->section->addText(
                "CONTRATO DE ACTUACION DE ARTISTAS",
                $titleStyle['font'],
                $titleStyle['paragraph']
            );
            $this->addMultipleLineBreaks(1);
        } catch (Exception $e) {
            throw new Exception("Error al añadir el título: " . $e->getMessage());
        }
    }

    // Añade la introducción del contrato
    private function addIntroduction()
    {
        try {
            $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
            $boldStyle = ['bold' => true];

            $this->addIntroductionText($textRun, $boldStyle);
            $this->addMultipleLineBreaks(1);
        } catch (Exception $e) {
            throw new Exception("Error al añadir la introducción: " . $e->getMessage());
        }
    }

    // Agrega el texto de introducción
    private function addIntroductionText($textRun, $boldStyle)
    {
        $introText = [
            ["Entre la ", null],
            ["PRODUCTORA", $boldStyle],
            [" de eventos artísticos y representante legal de este, la señorita ", null],
            ["OLGA XIMENA SCHAAF GODOY", $boldStyle],
            [", Rut: ", null],
            ["11.704.321-5", $boldStyle],
            [", con domicilio: El Castaño N°01976, Alto del Maitén, Provincia de Melipilla, Región Metropolitana, en adelante denominada ", null],
            ["SCHAAFPRODUCCIONES SpA", ['bold' => true, 'allCaps' => true]],
            [", Rut: ", null],
            ["76.748.346-5", $boldStyle],
            [" por una parte, y por la otra ", null],
            ["{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle],
            [" Rut: ", null],
            ["{$this->eventData['rut']}", $boldStyle],
            [", en adelante, en representación de ", null],
            ["{$this->eventData['nombre_empresa']}", ['bold' => true, 'allCaps' => true]],
            [", Rol Único Tributario Rut: ", null],
            ["{$this->eventData['rut_empresa']}", $boldStyle],
            [" con domicilio en: ", null],
            ["{$this->eventData['direccion_empresa']}", $boldStyle],
            [", se conviene en celebrar el presente contrato de actuación de artistas, contenido en las cláusulas siguientes:", null]
        ];

        foreach ($introText as $text) {
            $textRun->addText($text[0], $text[1]);
        }
    }

    // Funciones auxiliares
    private function addMultipleLineBreaks($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->section->addTextBreak();
        }
    }

    private function addPageBreak()
    {
        $this->section->addPageBreak();
    }
    // Función para agregar todas las cláusulas
    private function addClauses()
    {
        try {
            $this->addClause1();
            $this->addClause2();
            $this->addClause3();
            $this->addClause4();
            $this->addClause5();
            $this->addPageBreak();
            $this->addClause6();
            $this->addClause7();
        } catch (Exception $e) {
            throw new Exception("Error al añadir las cláusulas: " . $e->getMessage());
        }
    }

    // Cláusula 1: Objeto del Contrato
    private function addClause1()
    {
        try {
            $this->section->addText(
                "Cláusula 1: OBJETO DEL CONTRATO",
                ['bold' => true],
                ['spaceAfter' => 100, 'spaceBefore' => 100]
            );

            $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
            $boldStyle = ['bold' => true];

            $clause1Text = [
                ["{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle],
                [" contrata los servicios del siguiente artista: ", null],
                ["AGRUPACIÓN MARILYN", $boldStyle],
                [" el mencionado, en adelante y a los efectos del presente contrato denominado, el artista, efectuará una (1) presentación de aproximadamente 60 minutos, a realizarse en el marco de presentación pública, el día ", null],
                [$this->convertirFecha($this->eventData['fecha_evento']), $boldStyle],
                [" a las ", null],
                [mb_strtoupper(date('H:i', strtotime($this->eventData['hora_evento'])), 'UTF-8'), $boldStyle],
                [" en ", null],
                [$this->eventData['lugar_evento'], $boldStyle],
                [" para el evento ", null],
                [$this->eventData['nombre_evento'], $boldStyle]
            ];

            foreach ($clause1Text as $text) {
                $textRun->addText($text[0], $text[1]);
            }
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 1: " . $e->getMessage());
        }
    }

    // Cláusula 2: Remuneración
    private function addClause2()
    {
        try {
            $this->section->addText(
                "Cláusula 2: REMUNERACIÓN",
                ['bold' => true],
                ['spaceAfter' => 100, 'spaceBefore' => 100]
            );

            $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
            $boldStyle = ['bold' => true];

            $valor_evento = intval($this->eventData['valor_evento']);
            $valor_en_palabras = $this->numberToWords($valor_evento);
            $valor_formateado = number_format($valor_evento, 0, ',', '.');

            $mitad_valor = $valor_evento / 2;
            $mitad_valor_palabras = $this->numberToWords($mitad_valor);
            $mitad_valor_formateado = number_format($mitad_valor, 0, ',', '.');

            // Construir el texto de la cláusula 2
            $clause2Text = [
                ["2.1 Por la presentación mencionada en la Cláusula 1, ", null],
                ["{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle],
                [" Pagará a ", null],
                ["SCHAAFPRODUCCIONES SPA", $boldStyle],
                [" la cantidad de ", null],
                ["$" . $valor_formateado . " (" . $valor_en_palabras . " PESOS)", $boldStyle],
                [" La cantidad de ", null],
                ["$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " PESOS)", $boldStyle],
                [", correspondiente en parte a un 50% que será pagado a la firma del presente contrato en dinero en efectivo y solo moneda nacional o transferencia Bancaria.", null],
                [" La cantidad de ", null],
                ["$" . $mitad_valor_formateado . " (" . $mitad_valor_palabras . " PESOS)", $boldStyle],
                [", correspondiente al 50% restante, por concepto de término de honorarios, deberá ser cancelado antes de subir al escenario el día ", null],
                [$this->convertirFecha($this->eventData['fecha_evento']), $boldStyle],
                [", dinero en efectivo o transferencia Bancaria.", null],
                [" BANCO SANTANDER, CUENTA CORRIENTE N° 71760359, RUT:76.748.346-5, NOMBRE: SCHAAFPRODUCCIONES SPA, CORREO: SCHAAFPRODUCCIONES@GMAIL.COM", $boldStyle]
            ];

            foreach ($clause2Text as $text) {
                $textRun->addText($text[0], $text[1]);
            }
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 2: " . $e->getMessage());
        }
    }

    // Cláusula 3: Alojamiento, Traslados y Viáticos
    private function addClause3()
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 3: " . $e->getMessage());
        }
    }

    // Función para convertir números a palabras
    private function numberToWords($number)
    {
        $units = ["", "un", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve"];
        $tens = ["", "diez", "veinte", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"];
        $teens = ["diez", "once", "doce", "trece", "catorce", "quince", "dieciséis", "diecisiete", "dieciocho", "diecinueve"];
        $hundreds = ["", "ciento", "doscientos", "trescientos", "cuatrocientos", "quinientos", "seiscientos", "setecientos", "ochocientos", "novecientos"];

        if ($number == 0) return "cero";
        if ($number < 0) return "menos " . $this->numberToWords(abs($number));

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

        return mb_strtoupper(implode(" ", $words), 'UTF-8');
    }

    // Función para convertir fecha a formato texto
    private function convertirFecha($fecha)
    {
        try {
            $meses = [
                "ENERO",
                "FEBRERO",
                "MARZO",
                "ABRIL",
                "MAYO",
                "JUNIO",
                "JULIO",
                "AGOSTO",
                "SEPTIEMBRE",
                "OCTUBRE",
                "NOVIEMBRE",
                "DICIEMBRE"
            ];

            $fecha = new DateTime($fecha);
            $dia = $fecha->format('j');
            $mes = $meses[$fecha->format('n') - 1];
            $anio = $fecha->format('Y');

            return mb_strtoupper("$dia DE $mes DEL $anio", 'UTF-8');
        } catch (Exception $e) {
            throw new Exception("Error al convertir la fecha: " . $e->getMessage());
        }
    }
    // Cláusula 4: Suspensión
    private function addClause4()
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 4: " . $e->getMessage());
        }
    }

    // Cláusula 5: Promoción
    private function addClause5()
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 5: " . $e->getMessage());
        }
    }

    // Cláusula 6: Seguridad
    private function addClause6()
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 6: " . $e->getMessage());
        }
    }

    // Cláusula 7: Coordinación
    private function addClause7()
    {
        try {
            $this->section->addText("Cláusula 7: COORDINACIÓN", ['bold' => true], ['spaceAfter' => 100]);
            $textRun = $this->section->addTextRun(['alignment' => 'both', 'spaceAfter' => 100]);
            $textRun->addText("para los efectos de la realización del evento, las partes designan a las siguientes personas con sus respectivos datos: ");

            $this->section->addTextBreak();

            $leftAlignedStyle = ['alignment' => 'left'];
            $boldStyle = ['bold' => true];

            $this->section->addText("SCHAAFPRODUCCIONES SPA", $boldStyle, $leftAlignedStyle);
            $this->section->addText("SEÑORITA:   OLGA XIMENA SCHAAF GODOY", $boldStyle, $leftAlignedStyle);
            $this->section->addText("CELULAR:    +569995699801", $boldStyle, $leftAlignedStyle);
            $this->section->addText("CORREO:     ARTISTAS@SCHAAFPRODUCCIONES.CL", $boldStyle, $leftAlignedStyle);

            $tratamiento = (strtoupper($this->eventData['genero']) == 'FEMENINO') ? "SEÑORA:" : "SEÑOR:";
            $textRun = $this->section->addTextRun($leftAlignedStyle);
            $textRun->addText($tratamiento . "       ", $boldStyle);
            $textRun->addText("{$this->eventData['nombres']} {$this->eventData['apellidos']}", $boldStyle);

            $textRun = $this->section->addTextRun($leftAlignedStyle);
            $textRun->addText("CELULAR:     ", $boldStyle);
            $textRun->addText($this->eventData['celular'], $boldStyle);

            $textRun = $this->section->addTextRun($leftAlignedStyle);
            $textRun->addText("CORREO:     ", $boldStyle);
            $textRun->addText($this->eventData['correo'], $boldStyle);

            $this->addMultipleLineBreaks(5);
        } catch (Exception $e) {
            throw new Exception("Error al añadir la cláusula 7: " . $e->getMessage());
        }
    }

    // Agregar firmas al documento
    private function addSignatures()
    {
        try {
            $this->addMultipleLineBreaks(1);
            $table = $this->section->addTable(['alignment' => 'center']);
            $table->addRow();

            // Primera firma
            $cell1 = $table->addCell(6000);
            $this->addSignatureContent(
                $cell1,
                'firma.png',
                "OLGA XIMENA SCHAAF",
                "76.748.346-5",
                "SCHAAFPRODUCCIONES SpA"
            );

            // Segunda firma
            $cell2 = $table->addCell(3000);
            $this->addSignatureContent(
                $cell2,
                'firma-2.png',
                "{$this->eventData['nombres']} {$this->eventData['apellidos']}",
                $this->eventData['rut_empresa'],
                $this->eventData['nombre_empresa']
            );
        } catch (Exception $e) {
            throw new Exception("Error al añadir las firmas: " . $e->getMessage());
        }
    }

    // Agregar contenido de firma
    private function addSignatureContent($cell, $imageName, $nombre, $rut, $empresa)
    {
        $firmaPath = __DIR__ . '/assets/img/' . $imageName;
        if (!file_exists($firmaPath)) {
            throw new Exception("Imagen de firma no encontrada: $firmaPath");
        }

        $firmaStyle = [
            'width' => 100,
            'height' => 74,
            'alignment' => 'center',
            'marginBottom' => 5
        ];

        $cell->addImage($firmaPath, $firmaStyle);

        $boldStyle = ['bold' => true];
        $centerStyle = ['alignment' => 'center'];

        $cell->addText("___________________________", $boldStyle, $centerStyle);
        $cell->addText($nombre, $boldStyle, $centerStyle);
        $cell->addText($rut, $boldStyle, $centerStyle);
        $cell->addText($empresa, $boldStyle, $centerStyle);
    }
}

// Código de ejecución principal
try {
    // Validar ID del evento
    $evento_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$evento_id) {
        throw new InvalidArgumentException("ID de evento no válido");
    }

    // Crear el generador de contratos y procesar
    $contractGenerator = new ContractGenerator($conn);
    $contractGenerator->getEventData($evento_id);
    $contractGenerator->generateContract();
    $contractGenerator->saveDocument();
} catch (Exception $e) {
    error_log("Error en generar_contrato.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
