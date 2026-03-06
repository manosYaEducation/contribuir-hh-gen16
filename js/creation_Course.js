// ==========================================
// GESTIÓN DE CURSOS - courseConfig.js
// ==========================================

// Variables globales para almacenar datos
let courseData = {
    courseId: null,
    modules: [],
    userId: null,
    userName: null
};

let objectivesData = [];
let requirementsData = [];

// ==========================================
// FUNCIONES DE INICIALIZACIÓN
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupEventListeners();
    
    // Si estamos editando, cargar datos del curso
    const courseIdParam = new URLSearchParams(window.location.search).get('id');
    if (courseIdParam) {
        courseData.courseId = courseIdParam;
        loadCourseData(courseIdParam);
    }
});

function initializeForm() {
    const today = new Date().toISOString().split('T')[0];
    const startDateInput = document.getElementById('startDate');
    if (startDateInput) {
        startDateInput.value = today;
    }
    
    // Obtener datos de la sesión del usuario
    checkUserSession();
}

async function checkUserSession() {
    try {
        const response = await fetch('../api/check_session.php');
        const data = await response.json();
        
        if (data.loggedIn) {
            courseData.userId = data.user_id;
            courseData.userName = data.name;
            console.log('Usuario logueado:', data.name, 'ID:', data.user_id);
        } else {
            alert('Debes iniciar sesión para crear un curso');
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        alert('Error al verificar sesión');
    }
}

function setupEventListeners() {
    // Toggle para modo gratuito
    const isFreeCheckbox = document.getElementById('isFree');
    if (isFreeCheckbox) {
        isFreeCheckbox.addEventListener('change', toggleFreeMode);
    }

    // Drag and drop para imagen
    const uploadArea = document.querySelector('.image-upload');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('drop', handleImageDrop);
    }
}

// ==========================================
// FUNCIONES DE NAVEGACIÓN
// ==========================================
function switchSection(element, sectionId) {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    element.classList.add('active');

    document.querySelectorAll('.config-section').forEach(section => {
        section.classList.remove('active');
    });

    document.getElementById('section-' + sectionId).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goBack() {
    if (confirm('¿Estás seguro de salir? Los cambios no guardados se perderán.')) {
        window.location.href = '../index.html';
    }
}

// ==========================================
// FUNCIONES DE IMAGEN
// ==========================================
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        validateImageFile(file);
    }
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.style.borderColor = '#1e3e5a';
    event.currentTarget.style.background = '#ebf4ff';
}

function handleImageDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const file = files[0];
        if (file.type.startsWith('image/')) {
            document.getElementById('courseImage').files = files;
            validateImageFile(file);
        }
    }
}

function validateImageFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        alert('La imagen no debe superar 5MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('imagePreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
    }
    reader.readAsDataURL(file);
}

// ==========================================
// FUNCIONES DE MÓDULOS
// ==========================================
function addModule() {
    const modulesList = document.getElementById('modulesList');
    const modules = document.querySelectorAll('.module-item');
    const moduleCount = modules.length + 1;
    const btnAdd = document.querySelector('.btn-add');

    const newModule = document.createElement('div');
    newModule.className = 'module-item';
    newModule.setAttribute('data-module-id', moduleCount);

    newModule.innerHTML = `
        <div class="module-header">
            <div class="module-header-info">
                <div class="module-number">${moduleCount}</div>
                <input type="text" class="module-title-input" placeholder="Título del módulo" required>
            </div>
            <div class="module-actions">
                <button type="button" class="icon-btn delete" title="Eliminar" onclick="removeModule(this)">🗑️</button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">
                Duración de la Lección (Horas) <span class="required">*</span>
            </label>
            <input type="number" class="form-input module-duration" min="1" placeholder="Ej: 2" required>
            <span class="form-help">Duración en Horas</span>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                Descripción <span class="required">*</span>
            </label>
            <textarea class="form-textarea module-description" maxlength="200" required></textarea>
            <span class="form-help">Máximo 200 caracteres</span>
        </div>

        <div class="form-group">
            <label class="form-label">
                Contenido <span class="required">*</span>
            </label>
            <textarea class="form-textarea module-content" required></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">
                Complemento del módulo
            </label>
            <input type="text" class="form-input module-complement" placeholder="Ej: https://www.youtube.com/ o https://docs.google.com/document/" required>
            <span class="form-help">Pega un link de un archivo o un video</span>
        </div>
    `;

    modulesList.insertBefore(newModule, btnAdd);
}

function removeModule(button) {
    if (confirm('¿Estás seguro de eliminar este módulo?')) {
        const moduleItem = button.closest('.module-item');
        moduleItem.remove();
        updateModuleNumbers();
    }
}

function updateModuleNumbers() {
    document.querySelectorAll('.module-number').forEach((number, index) => {
        number.textContent = index + 1;
    });
}

// ==========================================
// FUNCIONES DE COSTO (TOGGLE)
// ==========================================
function toggleFreeMode(event) {
    const checkbox = event.target;
    const priceInput = document.getElementById('price');
    
    if (checkbox.checked) {
        priceInput.disabled = true;
        priceInput.value = '0';
        priceInput.style.opacity = '0.5';
    } else {
        priceInput.disabled = false;
        priceInput.style.opacity = '1';
    }
}

// ==========================================
// FUNCIONES DE ITEMS (OBJETIVOS Y REQUISITOS)
// ==========================================
function handleItemInput(event, type) {
    if (event.key !== 'Enter') return;
    event.preventDefault();

    const input = event.target;
    const itemText = input.value.trim();

    if (!itemText) {
        alert('Por favor escribe algo antes de presionar Enter');
        return;
    }

    if (type === 'objectives') {
        objectivesData.push(itemText);
        renderObjectives();
    } else if (type === 'requirements') {
        requirementsData.push(itemText);
        renderRequirements();
    }

    input.value = '';
    input.focus();
}

function renderObjectives() {
    const container = document.getElementById('objectivesList');
    container.innerHTML = '';
    objectivesData.forEach((objective, index) => {
        const tag = document.createElement('div');
        tag.className = 'item-tag';
        tag.innerHTML = `
            <span class="item-tag-text">✓ ${objective}</span>
            <span class="item-remove" onclick="removeObjective(${index})" title="Eliminar">×</span>
        `;
        container.appendChild(tag);
    });
}

function renderRequirements() {
    const container = document.getElementById('requirementsList');
    container.innerHTML = '';
    requirementsData.forEach((requirement, index) => {
        const tag = document.createElement('div');
        tag.className = 'item-tag';
        tag.innerHTML = `
            <span class="item-tag-text">✓ ${requirement}</span>
            <span class="item-remove" onclick="removeRequirement(${index})" title="Eliminar">×</span>
        `;
        container.appendChild(tag);
    });
}

function removeObjective(index) {
    objectivesData.splice(index, 1);
    renderObjectives();
}

function removeRequirement(index) {
    requirementsData.splice(index, 1);
    renderRequirements();
}

// ==========================================
// VALIDACIÓN DE FORMULARIO
// ==========================================
function validateForm() {
    const errors = {};

    // Validar campos generales
    const courseTitle = document.getElementById('courseTitle')?.value?.trim();
    if (!courseTitle) errors.courseTitle = 'El título es requerido';

    const shortDescription = document.getElementById('shortDescription')?.value?.trim();
    if (!shortDescription) errors.shortDescription = 'La descripción es requerida';

   // const instructor = document.getElementById('instructor')?.value?.trim();
    //if (!instructor) errors.instructor = 'El instructor es requerido';

    const category = document.getElementById('category')?.value;
    if (!category) errors.category = 'La categoría es requerida';

    const duration = document.getElementById('duration')?.value;
    if (!duration || duration < 1) errors.duration = 'La duración es requerida';

    //const introVideoUrl = document.getElementById('introVideoUrl')?.value?.trim();
    //if (!introVideoUrl) errors.introVideoUrl = 'El video de introducción es requerido';

    // Mostrar errores
    Object.keys(errors).forEach(fieldId => {
        const errorElement = document.getElementById(`error-${fieldId}`);
        const field = document.getElementById(fieldId);
        if (errorElement) {
            errorElement.classList.add('show');
        }
        if (field) {
            field.classList.add('error');
        }
    });

    return Object.keys(errors).length === 0;
}

// ==========================================
// FUNCIONES DE GUARDADO
// ==========================================
async function saveCourse() {
    if (!validateForm()) {
        alert('Por favor completa todos los campos requeridos');
        return;
    }

    const btn = document.getElementById('saveCourseBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Guardando...';
    btn.disabled = true;

    try {
        // 1. Guardar curso principal
        const courseMainData = await saveCourseMain();
        if (!courseMainData.courseId) {
            throw new Error(courseMainData.error || 'Error al guardar el curso');
        }

        const courseId = courseMainData.courseId;
        courseData.courseId = courseId;

        // 2. Guardar detalles del curso
        await saveCourseDetails(courseId);

        // 3. Guardar lecciones (módulos)
        await saveLessons(courseId);

        showSuccessMessage(btn, originalText);
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar el curso: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ==========================================
// FUNCIONES API - AGREGAR CURSO
// ==========================================
async function saveCourseMain() {
    const formData = new FormData();
    
    // Mapeo de campos para compatibilidad con add_course.php existente
    formData.append('title', document.getElementById('courseTitle').value);
    formData.append('description', document.getElementById('shortDescription').value);
    formData.append('instructor_id', courseData.userId); // Usar el ID del usuario logueado
    formData.append('category', document.getElementById('category').value);
    formData.append('price', document.getElementById('isFree').checked ? 0 : document.getElementById('price').value);
    formData.append('duration', document.getElementById('duration').value);
    formData.append('rating', 0); // Rating inicial
    formData.append('students', 0); // Estudiantes iniciales
    formData.append('image', 'photos/LogoOficial.png'); // Imagen por defecto
    formData.append('avatar', 'photos/usuario_sin_imagen.png'); // Avatar por defecto

    // Agregar imagen si existe
    const imageFile = document.getElementById('courseImage').files[0];
    if (imageFile) {
        formData.append('image', 'uploads/' + imageFile.name);
    }

    try {
        const response = await fetch('../api/add_course.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        return data;
    } catch (error) {
        throw new Error('Error en la solicitud: ' + error.message);
    }
}

// ==========================================
// FUNCIONES API - AGREGAR DETALLES DEL CURSO
// ==========================================
async function saveCourseDetails(courseId) {
    const payload = {
        course_id: courseId,
        learning_objectives: objectivesData,  // Cambio: objetivos → learning_objectives
        requirements: requirementsData,        // Cambio: requisitos → requirements
        intro_video: document.getElementById('introVideoUrl').value  // Cambio: intro_video_url → intro_video
    };

    try {
        const response = await fetch('../api/add_course_detail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!data.success && data.error) {
            throw new Error(data.error || 'Error al guardar detalles');
        }
        return data;
    } catch (error) {
        throw new Error('Error al guardar detalles: ' + error.message);
    }
}

// ==========================================
// FUNCIONES API - AGREGAR LECCIONES (MÓDULOS)
// ==========================================
async function saveLessons(courseId) {
    const modules = document.querySelectorAll('.module-item');
    
    for (let i = 0; i < modules.length; i++) {
        const module = modules[i];
        
        // Usar FormData porque add_lesson.php espera POST
        const formData = new FormData();
        formData.append('course_id', courseId);
        formData.append('order_number', i + 1);  // Cambio: lesson_number → order_number
        formData.append('title', module.querySelector('.module-title-input').value);
        formData.append('duration', module.querySelector('.module-duration').value);
        formData.append('description', module.querySelector('.module-description').value);
        formData.append('content', module.querySelector('.module-content').value);
        formData.append('video_url', module.querySelector('.module-complement').value);  // Cambio: complement_url → video_url
        formData.append('is_free', 0);

        try {
            const response = await fetch('../api/add_lesson.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success && data.error) {
                throw new Error(`Error al guardar lección ${i + 1}: ${data.error}`);
            }
        } catch (error) {
            throw new Error('Error al guardar lecciones: ' + error.message);
        }
    }
}

// ==========================================
// FUNCIONES DE UTILIDAD
// ==========================================
function showSuccessMessage(btn, originalText) {
    btn.innerHTML = '✓ Guardado';
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('✓ Curso guardado exitosamente');
        window.location.href = '../index.html';
    }, 1500);
}

async function loadCourseData(courseId) {
    try {
        // Cargar datos del curso para edición
        console.log('Cargando datos del curso:', courseId);
        // Aquí iría la lógica para cargar curso existente
    } catch (error) {
        console.error('Error al cargar datos:', error);
    }
}

function previewCourse() {
    if (!validateForm()) {
        alert('Por favor completa los campos básicos primero');
        return;
    }
    alert('Abriendo vista previa del curso...');
}
