<?php
require_once 'config/config.php';
require_once 'functions/functions.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Verificar autenticación
session_start();
checkAuthentication();

class MYPDF extends TCPDF {
    public function Header() {
        $image_file = K_PATH_IMAGES.'logo.jpg';
        if(file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'Itinerario de Eventos', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', 'I', 8);
        $this->SetXY(10, 20);
        $this->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

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

// Consulta SQL mejorada para obtener eventos activos con nombre de artista
$sql = "SELECT e.*, a.nombre as nombre_artista 
        FROM eventos e 
        LEFT JOIN artistas a ON e.artista_id = a.id
        WHERE e.estado_evento IN ('Confirmado', 'En Producción') 
        ORDER BY e.fecha_evento ASC";

$result = $conn->query($sql);

// Definir el ancho de las columnas para mejor alineación
$html = '<style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 0;
                padding: 0;
                table-layout: fixed;
            }
            td, th {
                border: 1px solid #000;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                padding: 6px 4px;
            }
            thead tr {
                background-color: #f5f5f5;
            }
         </style>
         <table cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th width="20%" align="center" style="font-weight: bold; font-size: 11px;">Fecha</th>
                    <th width="20%" align="center" style="font-weight: bold; font-size: 11px;">Hora</th>
                    <th width="20%" align="center" style="font-weight: bold; font-size: 11px;">Ciudad</th>
                    <th width="20%" align="center" style="font-weight: bold; font-size: 11px;">Evento</th>
                    <th width="20%" align="center" style="font-weight: bold; font-size: 11px;">Artista</th>
                </tr>
            </thead>
            <tbody>';

// Agregar datos a la tabla con mejor formato
while ($evento = $result->fetch_assoc()) {
    $fecha = date('d/m/Y', strtotime($evento['fecha_evento']));
    $hora = $evento['hora_evento'] ? date('H:i', strtotime($evento['hora_evento'])) : 'Por definir';
    $ciudad = htmlspecialchars($evento['ciudad_evento']);
    $nombreEvento = htmlspecialchars($evento['nombre_evento']);
    $nombreArtista = htmlspecialchars($evento['nombre_artista'] ?: 'Por confirmar');
    
    // Preparar los datos para evitar saltos de línea
    $nombreEvento = str_replace(' ', '&nbsp;', $nombreEvento);
    $nombreArtista = str_replace(' ', '&nbsp;', $nombreArtista);
    $ciudad = str_replace(' ', '&nbsp;', $ciudad);

    $html .= sprintf(
        '<tr>
            <td align="center" style="font-size: 10px;">%s</td>
            <td align="center" style="font-size: 10px;">%s</td>
            <td align="center" style="font-size: 10px;">%s</td>
            <td align="left" style="font-size: 10px;">%s</td>
            <td align="left" style="font-size: 10px;">%s</td>
        </tr>',
        $fecha,
        $hora,
        $ciudad,
        $nombreEvento,
        $nombreArtista
    );
}

$html .= '</tbody></table>';

// Ajustar el estilo de la tabla
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar el PDF
$fecha_actual = date('d-m-Y');
$pdf->Output('Itinerario Eventos ' . $fecha_actual . '.pdf', 'D');

// Cerrar la conexión a la base de datos
$conn->close();
?>