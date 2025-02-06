// file-upload.js
class FileUploadManager {
    constructor(options = {}) {
        this.options = {
            maxSize: 10 * 1024 * 1024, // 10MB por defecto
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif'],
            inputSelector: '',
            previewSelector: '',
            containerSelector: '',
            ...options
        };

        // Obtener elementos del DOM
        this.container = document.querySelector(this.options.containerSelector);
        if (!this.container) {
            console.error(`Contenedor no encontrado: ${this.options.containerSelector}`);
            return;
        }

        this.input = this.container.querySelector('input[type="file"]');
        this.preview = this.container.querySelector('.preview-container img');
        this.previewContainer = this.container.querySelector('.preview-container');
        this.removeButton = this.container.querySelector('.btn-remove');
        this.fileLabel = this.container.querySelector('.file-label');

        if (!this.input || !this.preview || !this.previewContainer) {
            console.error('No se encontraron elementos necesarios en el contenedor');
            return;
        }

        this.initializeEvents();
    }

    initializeEvents() {
        // Prevenir comportamiento por defecto del navegador para drag & drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.container.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
            document.body.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Eventos de arrastrar y soltar
        ['dragenter', 'dragover'].forEach(eventName => {
            this.container.addEventListener(eventName, () => {
                this.container.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.container.addEventListener(eventName, () => {
                this.container.classList.remove('dragover');
            }, false);
        });

        // Manejar el drop
        this.container.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length) {
                this.handleFile(files[0]);
            }
        }, false);

        // Evento de selección de archivo
        this.input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                this.handleFile(e.target.files[0]);
            }
        });

        // Evento de remover archivo
        if (this.removeButton) {
            this.removeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeFile();
            });
        }

        // Prevenir que el click en el botón de remover abra el diálogo de archivo
        this.previewContainer.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Mejorar la experiencia del usuario al hacer clic en el label
        if (this.fileLabel) {
            this.fileLabel.addEventListener('click', (e) => {
                e.preventDefault();
                this.input.click();
            });
        }
    }

    handleFile(file) {
        return new Promise((resolve, reject) => {
            // Validar tipo de archivo
            if (!this.options.allowedTypes.includes(file.type)) {
                this.showError('Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, GIF)');
                reject(new Error('Tipo de archivo no válido'));
                return;
            }

            // Validar tamaño
            if (file.size > this.options.maxSize) {
                this.showError(`El archivo excede el tamaño máximo permitido (${this.formatSize(this.options.maxSize)})`);
                reject(new Error('Tamaño de archivo excedido'));
                return;
            }

            // Previsualizar imagen
            const reader = new FileReader();
            reader.onload = (e) => {
                this.preview.src = e.target.result;
                this.container.classList.add('has-file');
                this.triggerEvent('fileSelected', { file });
                resolve(file);
            };

            reader.onerror = () => {
                this.showError('Error al leer el archivo');
                reject(new Error('Error de lectura'));
            };

            reader.readAsDataURL(file);
        });
    }

    removeFile() {
        this.input.value = '';
        this.preview.src = '';
        this.container.classList.remove('has-file');
        this.triggerEvent('fileRemoved');
    }

    formatSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            footer: `Tamaño máximo permitido: ${this.formatSize(this.options.maxSize)}`
        });
    }

    triggerEvent(name, detail = {}) {
        this.container.dispatchEvent(new CustomEvent(name, { 
            detail,
            bubbles: true 
        }));
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    try {
        // Inicializar gestores de archivos
        const managers = [
            new FileUploadManager({
                containerSelector: '#container_imagen_presentacion',
                maxSize: 10 * 1024 * 1024,
                allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
            }),
            new FileUploadManager({
                containerSelector: '#container_logo_artista',
                maxSize: 10 * 1024 * 1024,
                allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
            })
        ];

        // Configurar el formulario
        const form = document.getElementById('artistaForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Validar campos requeridos
                const requiredFields = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
                let isValid = true;

                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'Por favor complete todos los campos requeridos'
                    });
                    return;
                }

                try {
                    // Mostrar loader
                    await Swal.fire({
                        title: 'Procesando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const formData = new FormData(form);

                    const response = await fetch('functions/procesar_artista.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message,
                            confirmButtonText: 'Ok'
                        });
                        
                        // Redirección con delay
                        setTimeout(() => {
                            window.location.href = 'listar_artistas.php';
                        }, 500);
                    } else {
                        throw new Error(data.error || 'Error al procesar el formulario');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Ocurrió un error al procesar la solicitud',
                        footer: 'Por favor, intente nuevamente'
                    });
                }
            });

            // Remover clases de validación al escribir
            form.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('input', () => {
                    input.classList.remove('is-invalid');
                });
            });
        }
    } catch (error) {
        console.error('Error al inicializar los gestores de archivos:', error);
    }
});