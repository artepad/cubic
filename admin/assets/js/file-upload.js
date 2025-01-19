class FileUploadManager {
    constructor(options = {}) {
        this.options = {
            maxSize: 2 * 1024 * 1024, // 2MB por defecto
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
        // Evento para arrastrar y soltar
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
        
        // Evento para selección de archivo
        this.input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                this.handleFile(e.target.files[0]);
            }
        });
        
        // Botón para remover archivo
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
    
    showError(message) {
        // Usar SweetAlert2 para mostrar errores
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonText: 'Entendido'
        });
    }
    
    formatSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
    const managers = [];
    
    // Imagen Principal
    managers.push(new FileUploadManager({
        inputSelector: '#imagen_presentacion',
        previewSelector: '#preview_imagen',
        containerSelector: '#container_imagen_presentacion'
    }));
    
    // Logo
    managers.push(new FileUploadManager({
        inputSelector: '#logo_artista',
        previewSelector: '#preview_logo',
        containerSelector: '#container_logo_artista'
    }));
    
    // Manejar eventos del formulario
    const form = document.getElementById('artistaForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return false;
            }
            
            await submitForm(this);
        });
    }
});

// Función para validar el formulario
function validateForm() {
    let isValid = true;
    const requiredFields = {
        'nombre': 'Nombre',
        'genero_musical': 'Género Musical',
        'descripcion': 'Descripción',
        'presentacion': 'Presentación'
    };

    // Validar campos requeridos
    Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            showError(`El campo "${fieldName}" es requerido`);
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Función para enviar el formulario
async function submitForm(form) {
    showLoader('Guardando artista');

    try {
        const formData = new FormData(form);
        const response = await fetch('functions/procesar_artista.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error al guardar el artista');
        }

        await showSuccess('Artista guardado correctamente');
        window.location.href = 'listar_artistas.php';
    } catch (error) {
        showError(error.message || 'Error al guardar el artista');
    }
}

// Funciones auxiliares UI
function showLoader(message) {
    Swal.fire({
        title: message,
        text: 'Por favor espere...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
    });
}

function showSuccess(message) {
    return Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: message,
        confirmButtonText: 'OK'
    });
}
async function submitForm(form) {
    showLoader('Guardando artista');

    try {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        if (!csrfToken) {
            throw new Error('Token CSRF no encontrado');
        }

        const response = await fetch('functions/procesar_artista.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Error al guardar el artista');
        }

        const data = await response.json();

        if (data.success) {
            await showSuccess('Artista guardado correctamente');
            window.location.href = 'listar_artistas.php';
        } else {
            throw new Error(data.error || 'Error al guardar el artista');
        }
    } catch (error) {
        console.error('Error:', error);
        showError(error.message || 'Error al guardar el artista');
    }
}