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
        // Validar tipo de archivo
        if (!this.options.allowedTypes.includes(file.type)) {
            this.showError('Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, GIF)');
            return false;
        }
        
        // Validar tamaño
        if (file.size > this.options.maxSize) {
            this.showError(`El archivo excede el tamaño máximo permitido (${this.formatSize(this.options.maxSize)})`);
            return false;
        }
        
        // Previsualizar imagen
        const reader = new FileReader();
        reader.onload = (e) => {
            this.preview.src = e.target.result;
            this.container.classList.add('has-file');
            this.triggerEvent('fileSelected', { file });
        };
        reader.readAsDataURL(file);
        
        return true;
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

// Inicialización del gestor para cada campo de archivo
document.addEventListener('DOMContentLoaded', () => {
    const managers = [
        new FileUploadManager({
            inputSelector: '#imagen_presentacion',
            previewSelector: '#preview_imagen',
            containerSelector: '#container_imagen_presentacion',
            maxSize: 10 * 1024 * 1024 // 10MB
        }),
        new FileUploadManager({
            inputSelector: '#logo_artista',
            previewSelector: '#preview_logo',
            containerSelector: '#container_logo_artista',
            maxSize: 10 * 1024 * 1024 // 10MB
        })
    ];
});