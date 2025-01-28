-- ========================================
-- Datos iniciales
-- ========================================

-- Usuario administrador inicial (password: admin123)
INSERT INTO usuarios (username, password, nombre, email) VALUES 
('admin', '$2y$10$R65JBBwJOc3ZnLyqPHpeS.TXe1bsHfvjOXKl3YDFB87yl6nMT33E.', 'miguel', 'nuevo_admin@example.com');

-- Inserción de clientes
INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES
('María', 'Silva Rojas', '11.222.333-4', 'maria.silva@municipalidadtemuco.cl', '+56987654321', 'Femenino'),
('Carlos', 'Muñoz Vega', '10.111.222-3', 'carlos.munoz@munivalpo.cl', '+56923456789', 'Masculino'),
('Andrea', 'López Castro', '9.876.543-2', 'andrea.lopez@empresaXYZ.cl', '+56934567890', 'Femenino'),
('Ana', 'Martínez Soto', '14.555.666-7', 'ana.martinez@municipalidadosorno.cl', '+56956789012', 'Femenino'),
('Diego', 'Torres Mora', '11.222.333-4', 'diego.torres@munipuertomontt.cl', '+56989012345', 'Masculino'),
('Patricia', 'Castro Lagos', '10.111.222-3', 'patricia.castro@eventos.cl', '+56990123456', 'Femenino'),
('Valentina', 'Parra Soto', '15.999.888-7', 'valentina.parra@muniiquique.cl', '+56912345678', 'Femenino'),
('Ricardo', 'Bravo Silva', '14.888.777-6', 'ricardo.bravo@eventos.cl', '+56923456789', 'Masculino'),
('Jorge', 'Fuentes Rojas', '12.666.555-4', 'jorge.fuentes@muniarica.cl', '+56945678901', 'Masculino'),
('Daniela', 'Riquelme Pinto', '16.777.888-9', 'daniela.riquelme@municoncon.cl', '+56956781234', 'Femenino'),
('Isabel', 'Montenegro Cruz', '13.666.777-8', 'isabel.montenegro@muniquilpue.cl', '+56978903456', 'Femenino'),
('Andrés', 'Espinoza Lagos', '14.555.666-7', 'andres.espinoza@eventos.cl', '+56989014567', 'Masculino'),
('Catalina', 'Vargas Silva', '14.000.111-2', 'catalina.vargas@municalama.cl', '+56934569012', 'Femenino');

-- Inserción de empresas
INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES
('Municipalidad de Temuco', '69.190.700-7', 'Arturo Prat 650, Temuco', 1),
('Municipalidad de Valparaíso', '69.060.700-6', 'Condell 1490, Valparaíso', 2),
('Eventos XYZ SpA', '76.123.456-7', 'Los Carrera 567, Santiago', 3),
('Municipalidad de Osorno', '69.210.100-8', 'Mackenna 851, Osorno', 4),
('Municipalidad de Puerto Montt', '69.220.100-2', 'San Felipe 230, Puerto Montt', 5),
('Productora Nacional SpA', '76.555.666-7', 'Bulnes 456, Santiago', 6),
('Municipalidad de Iquique', '69.170.100-4', 'Serrano 145, Iquique', 7),
('Eventos Pacific Ltda.', '77.444.555-6', 'Baquedano 950, Antofagasta', 8),
('Municipalidad de Arica', '69.010.100-9', 'Rafael Sotomayor 415, Arica', 9),
('Municipalidad de Concón', '69.220.200-3', 'Santa Laura 567, Concón', 10),
('Municipalidad de Quilpué', '69.220.300-4', 'Vicuña Mackenna 684, Quilpué', 11),
('Eventos del Pacífico SpA', '76.666.777-8', 'Libertad 890, Viña del Mar', 12),
('Municipalidad de Calama', '69.220.400-5', 'Vicuña Mackenna 2001, Calama', 13);

-- Artistas de ejemplo
INSERT INTO `artistas` (`id`, `nombre`, `descripcion`, `presentacion`, `genero_musical`, `imagen_presentacion`, `logo_artista`, `fecha_creacion`) VALUES
(1, 'Agrupación Marilyn', 'Banda de cumbia argentina formada en 2006 conocida por su estilo testimonial y letras románticas. Su primer álbum fue Disco de Oro y fueron considerados la revelación musical de 2007.', 'Agrupación Marilyn ha conseguido un lugar especial en el corazón de seguidores tanto a nivel nacional como internacional. Su música, definida por la cumbia romántica y testimonial, narra historias que reflejan el cotidiano vivir con las cuales todos podemos identificarnos. Entre sus éxitos destacan Su florcita, Me enamoré, Te falta sufrir y Madre soltera. Actualmente, Agrupación Marilyn trabaja en su sexto disco, del cual ya han lanzado los exitosos singles: Abismo, Siento, Piel y Huesos, que adelantan una propuesta fresca y poderosa, fiel a su estilo.', 'Cumbia Testimonial', 'uploads/artistas/agrupaci__n_marilyn_1737831590/presentacion_679534a664056.png', 'uploads/artistas/agrupaci__n_marilyn_1737831590/logo_679534a664449.png', '2025-01-25 18:59:50'),
(2, 'Flor Álvarez', 'Cantante argentina que pasó de cantar en la calle a ser una estrella viral en TikTok. En 2023 comenzó su carrera profesional y ha logrado éxitos importantes en el género cumbia.', 'Agradecemos desde ya su interés en la talentosa cantante argentina Flor Álvarez, una joven promesa que ha conquistado corazones con su música. Desde sus inicios cantando en el subte de Buenos Aires, Flor ha logrado posicionarse como una figura destacada en la música urbana y cumbia romántica. Con éxitos como Con Vos, Tattoo, Me Toco Perder, El Amor de mi Vida, y Sin Querer, acumula millones de reproducciones. Su música refleja una propuesta fresca y emotiva, consolidando su lugar en la escena musical. Actualmente, trabaja en nuevas colaboraciones que prometen sorprender, llevando su música a niveles internacionales.', 'Cumbia', 'uploads/artistas/flor___lvarez_1737831991/presentacion_67953637e1ae5.png', 'uploads/artistas/flor___lvarez_1737831991/logo_67953637e1dc0.png', '2025-01-25 19:06:31');

-- Gira de ejemplo
INSERT INTO giras (nombre, fecha_creacion) 
VALUES ('Gira Verano 2025', '2025-01-15 10:00:00');


-- Evento de ejemplo
INSERT INTO eventos (cliente_id, gira_id, artista_id, nombre_evento, fecha_evento, hora_evento, ciudad_evento, lugar_evento, valor_evento, tipo_evento, encabezado_evento, estado_evento, hotel, traslados, viaticos) VALUES
(1, 1, 1, 'Festival Verano Temuco', '2025-02-01', '21:00:00', 'Temuco', 'Estadio German Becker', 18500000, 'Festival', 'Gran Festival de Verano con Agrupación Marilyn', 'Confirmado', 'Si', 'Si', 'Si'),
(2, 1, 2, 'Noche de Cumbia Valparaíso', '2025-02-03', '22:00:00', 'Valparaíso', 'Muelle Barón', 17850000, 'Show Privado', 'Flor Alvarez en Valparaíso', 'Confirmado', 'Si', 'Si', 'Si'),
(3, 1, 1, 'Carnaval Arica', '2025-02-05', '20:00:00', 'Arica', 'Plaza Colón', 19500000, 'Festival', 'Carnaval con Agrupación Marilyn', 'Confirmado', 'Si', 'Si', 'Si'),
(4, 1, 2, 'Festival del Norte', '2025-02-07', '21:30:00', 'Iquique', 'Estadio Tierra de Campeones', 22000000, 'Festival', 'Flor Alvarez en Festival del Norte', 'Confirmado', 'Si', 'Si', 'Si'),
(5, 1, 1, 'Verano Antofagasta', '2025-02-09', '21:00:00', 'Antofagasta', 'Estadio Regional', 20500000, 'Festival', 'Agrupación Marilyn en Antofagasta', 'Confirmado', 'Si', 'Si', 'Si'),
(6, 1, 2, 'Cumbia en La Serena', '2025-02-11', '22:00:00', 'La Serena', 'Parque Pedro de Valdivia', 16800000, 'Show Municipal', 'Flor Alvarez en La Serena', 'Confirmado', 'Si', 'Si', 'Si'),
(7, 1, 1, 'Festival de Viña', '2025-02-14', '22:30:00', 'Viña del Mar', 'Quinta Vergara', 24500000, 'Festival', 'Agrupación Marilyn en Viña', 'Confirmado', 'Si', 'Si', 'Si'),
(8, 1, 1, 'Verano Rancagua', '2025-02-18', '20:30:00', 'Rancagua', 'Medialuna Monumental', 17500000, 'Show Municipal', 'Agrupación Marilyn en Rancagua', 'Confirmado', 'Si', 'Si', 'Si'),
(9, 1, 2, 'Festival Talca', '2025-02-20', '21:00:00', 'Talca', 'Estadio Fiscal', 15800000, 'Festival', 'Flor Alvarez en Festival Talca', 'Confirmado', 'Si', 'Si', 'Si'),
(10, 1, 2, 'Verano Concepción', '2025-02-24', '22:00:00', 'Concepción', 'Estadio Ester Roa', 21500000, 'Festival', 'Flor Alvarez en Concepción', 'Confirmado', 'Si', 'Si', 'Si'),
(11, 1, 1, 'Festival Temuco', '2025-02-26', '21:00:00', 'Temuco', 'Gimnasio Olímpico', 19800000, 'Festival', 'Agrupación Marilyn en Festival Temuco', 'Confirmado', 'Si', 'Si', 'Si'),
(12, 1, 2, 'Noche de Cumbia Valdivia', '2025-02-28', '21:30:00', 'Valdivia', 'Coliseo Municipal', 18500000, 'Show Municipal', 'Flor Alvarez en Valdivia', 'Confirmado', 'Si', 'Si', 'Si'),
(13, 1, 1, 'Gran Final Puerto Montt', '2025-02-28', '22:00:00', 'Puerto Montt', 'Arena Puerto Montt', 24000000, 'Festival', 'Agrupación Marilyn Cierre de Gira', 'Confirmado', 'Si', 'Si', 'Si');