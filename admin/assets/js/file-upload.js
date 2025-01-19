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
        
        const removeButton = this.container.querySelector('.btn-remove');
        if (removeButton) {
            removeButton.addEventListener('click', () => this.removeFile());
        }
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
document.addEventListener('DOMContentLoaded', () => {
    // Primero inicializamos los manejadores de archivos
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

    // Luego configuramos el manejador del formulario
    const form = document.getElementById('artistaForm');
    
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Mostrar loader
            Swal.fire({
                title: 'Guardando artista...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const formData = new FormData(form);

                const response = await fetch('functions/procesar_artista.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Éxito
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Artista guardado correctamente',
                        confirmButtonText: 'Ok'
                    });
                    
                    // Redireccionar a la lista de artistas
                    window.location.href = 'listar_artistas.php';
                } else {
                    // Error con mensaje del servidor
                    throw new Error(data.error || 'Error al guardar el artista');
                }
            } catch (error) {
                console.error('Error:', error);
                
                // Mostrar error
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Ocurrió un error al procesar la solicitud',
                    footer: 'Por favor, intente nuevamente'
                });
            }
        });
    }
});