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
        
        this.input = document.querySelector(this.options.inputSelector);
        this.preview = document.querySelector(this.options.previewSelector);
        this.container = document.querySelector(this.options.containerSelector);
        
        if (!this.input || !this.preview || !this.container) {
            throw new Error('Selectores inválidos para el gestor de archivos');
        }
        
        this.initializeEvents();
    }
    
    initializeEvents() {
        this.container.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.container.classList.add('dragover');
        });
        
        this.container.addEventListener('dragleave', () => {
            this.container.classList.remove('dragover');
        });
        
        this.container.addEventListener('drop', (e) => {
            e.preventDefault();
            this.container.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) {
                this.handleFile(files[0]);
            }
        });
        
        this.input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                this.handleFile(e.target.files[0]);
            }
        });
    }
    
    handleFile(file) {
        // Mostrar indicador de carga
        this.container.classList.add('loading');
        
        return new Promise((resolve, reject) => {
            // Validar tipo de archivo
            if (!this.options.allowedTypes.includes(file.type)) {
                this.showError('Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, GIF)');
                this.container.classList.remove('loading');
                reject(new Error('Tipo de archivo no válido'));
                return;
            }
            
            // Validar tamaño
            if (file.size > this.options.maxSize) {
                this.showError(`El archivo excede el tamaño máximo permitido (${this.formatSize(this.options.maxSize)})`);
                this.container.classList.remove('loading');
                reject(new Error('Tamaño de archivo excedido'));
                return;
            }
            
            // Previsualizar imagen
            const reader = new FileReader();
            reader.onload = (e) => {
                this.preview.src = e.target.result;
                this.container.classList.add('has-file');
                this.container.classList.remove('loading');
                this.triggerEvent('fileSelected', { file });
                resolve(file);
            };
            
            reader.onerror = () => {
                this.showError('Error al leer el archivo');
                this.container.classList.remove('loading');
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

// Inicialización y manejo del formulario
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar manejadores de archivos
    const managers = [
        new FileUploadManager({
            inputSelector: '#imagen_presentacion',
            previewSelector: '#preview_imagen',
            containerSelector: '#container_imagen_presentacion',
            maxSize: 10 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
        }),
        new FileUploadManager({
            inputSelector: '#logo_artista',
            previewSelector: '#preview_logo',
            containerSelector: '#container_logo_artista',
            maxSize: 10 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
        })
    ];

    // Configurar manejador del formulario
    const form = document.getElementById('artistaForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validar campos requeridos antes de enviar
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

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        confirmButtonText: 'Ok'
                    });
                    
                    // Redirección con pequeño delay
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
});