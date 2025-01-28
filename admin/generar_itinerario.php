<?php
require_once 'config/config.php';
require_once 'functions/functions.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Verificar autenticación
session_start();
checkAuthentication();

// Crear nueva instancia de TCPDF
class MYPDF extends TCPDF {
    // Cabecera de página
    public function Header() {
        // Logo
        $image_file = K_PATH_IMAGES.'logo.jpg';
        if(file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Título
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'Itinerario de Eventos', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Fecha de generación
        $this->SetFont('helvetica', 'I', 8);
        $this->SetXY(10, 20);
        $this->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    // Pie de página
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Crear nuevo documento PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Establecer información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Schaaf Producciones');
$pdf->SetTitle('Itinerario de Eventos');

// Establecer márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 10, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Establecer saltos de página automáticos
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Agregar una página
$pdf->AddPage();

// Establecer fuente
$pdf->SetFont('helvetica', '', 10);

// Consulta SQL para obtener eventos activos
$sql = "SELECT e.*, a.nombre as nombre_artista 
        FROM eventos e 
        LEFT JOIN artistas a ON e.artista_id = a.id
        WHERE e.estado_evento IN ('Confirmado', 'En Producción') 
        ORDER BY e.fecha_evento ASC";

$result = $conn->query($sql);

// Crear tabla
$html = '<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th width="20%"><b>Fecha</b></th>
                    <th width="15%"><b>Hora</b></th>
                    <th width="25%"><b>Ciudad</b></th>
                    <th width="40%"><b>Evento</b></th>
                </tr>
            </thead>
            <tbody>';

// Agregar datos a la tabla
while ($evento = $result->fetch_assoc()) {
    $fecha = date('d/m/Y', strtotime($evento['fecha_evento']));
    $hora = $evento['hora_evento'] ? date('H:i', strtotime($evento['hora_evento'])) : 'Por definir';
    $ciudad = htmlspecialchars($evento['ciudad_evento']);
    $nombreEvento = htmlspecialchars($evento['nombre_evento']);
    
    $html .= "<tr>
                <td>{$fecha}</td>
                <td>{$hora}</td>
                <td>{$ciudad}</td>
                <td>{$nombreEvento}</td>
              </tr>";
}

$html .= '</tbody></table>';

// Escribir la tabla HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar el PDF
$pdf->Output('itinerario_eventos.pdf', 'D');

// Cerrar la conexión a la base de datos
$conn->close();
?>