/**
 * KODAN-HUB NEURAL UI
 * Animaciones y lógica de interfaz refinada.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 2. Animaciones de Entrada con GSAP
    if (typeof gsap !== 'undefined') {
        // Animación de las cards de stats
        gsap.from('.stat-card', {
            duration: 0.8,
            y: 30,
            stagger: 0.1,
            ease: "power4.out",
            delay: 0.2
        });

        // Animación del contenedor principal
        gsap.from('.glass-container', {
            duration: 1,
            opacity: 0,
            y: 20,
            ease: "power3.out",
            delay: 0.5
        });

        // Animación del Sidebar
        gsap.from('.sidebar', {
            duration: 0.8,
            x: -100,
            opacity: 0,
            ease: "power4.out"
        });
    }

    // 3. Lógica de Modales (Mejorada)
    window.showModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        
        modal.style.display = 'flex';
        gsap.fromTo(modal.querySelector('.modal-content'), 
            { scale: 0.9, opacity: 0 }, 
            { scale: 1, opacity: 1, duration: 0.4, ease: "back.out(1.7)" }
        );
        gsap.fromTo(modal, 
            { backgroundColor: 'rgba(0,0,0,0)' }, 
            { backgroundColor: 'rgba(0,0,0,0.8)', duration: 0.4 }
        );
    };

    window.hideModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;

        gsap.to(modal.querySelector('.modal-content'), {
            scale: 0.9,
            opacity: 0,
            duration: 0.3,
            ease: "power2.in",
            onComplete: () => {
                modal.style.display = 'none';
            }
        });
        gsap.to(modal, { backgroundColor: 'rgba(0,0,0,0)', duration: 0.3 });
    };

    // 4. Copiar al Portapapeles con feedback visual
    window.copyToClipboard = (text) => {
        navigator.clipboard.writeText(text).then(() => {
            showToast('TOKEN COPIADO AL PORTAPAPELES', 'success');
        });
    };
});

/**
 * Sistema de Notificaciones Neurales
 * @param {string} message 
 * @param {string} type 
 * @param {number} duration 0 para manual
 * @returns {HTMLElement} toast element
 */
function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return null;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.background = '#0a0a0a';
    toast.style.border = `1px solid ${type === 'success' ? '#00FFC2' : (type === 'error' ? '#ff4d4d' : 'rgba(255,255,255,0.2)')}`;
    toast.style.color = type === 'success' ? '#00FFC2' : (type === 'error' ? '#ff4d4d' : '#fff');
    toast.style.padding = '1rem 2rem';
    toast.style.borderRadius = '12px';
    toast.style.marginBottom = '10px';
    toast.style.boxShadow = '0 10px 40px rgba(0,0,0,0.9)';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.justifyContent = 'space-between';
    toast.style.minWidth = '320px';
    toast.style.fontSize = '0.8rem';
    toast.style.fontWeight = '700';
    toast.style.letterSpacing = '1px';
    
    const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-octagon' : 'refresh-cw');
    const spinClass = type === 'info' ? 'lucide-spin' : '';
    
    toast.innerHTML = `
        <span style="display:flex; align-items:center; gap:12px;">
            <i data-lucide="${icon}" style="width:18px;" class="${spinClass}"></i>
            ${message}
        </span>
    `;
    
    container.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    gsap.from(toast, { x: 100, opacity: 0, duration: 0.5, ease: "back.out(1.7)" });
    
    if (duration > 0) {
        setTimeout(() => {
            closeToast(toast);
        }, duration);
    }

    return toast;
}

function closeToast(toast) {
    if (!toast) return;
    gsap.to(toast, { 
        x: 100, 
        opacity: 0, 
        duration: 0.5, 
        onComplete: () => toast.remove() 
    });
}

/**
 * Renderiza la barra de paginación dinámicamente
 * @param {string} containerId 
 * @param {object} meta Datos de paginación (page, total_pages, total, limit)
 * @param {function} onPageChange Función a llamar al cambiar de página
 */
function renderPagination(containerId, meta, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container || !meta || meta.total_pages <= 1) {
        if(container) container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    let buttonsHtml = '';
    
    // Botón Anterior
    buttonsHtml += `<button class="page-btn" ${meta.page <= 1 ? 'disabled' : ''} onclick="window.${onPageChange.name}(${meta.page - 1})"><i data-lucide="chevron-left" style="width:14px;"></i></button>`;

    // Lógica de números de página (TimeTracker style)
    const showMax = 5;
    if (meta.total_pages <= showMax) {
        for (let i = 1; i <= meta.total_pages; i++) {
            buttonsHtml += `<button class="page-btn ${i === meta.page ? 'active' : ''}" onclick="window.${onPageChange.name}(${i})">${i}</button>`;
        }
    } else {
        buttonsHtml += `<button class="page-btn ${1 === meta.page ? 'active' : ''}" onclick="window.${onPageChange.name}(1)">1</button>`;
        if (meta.page > 3) buttonsHtml += `<span class="page-dots">...</span>`;
        
        const start = Math.max(2, meta.page - 1);
        const end = Math.min(meta.total_pages - 1, meta.page + 1);
        
        for (let i = start; i <= end; i++) {
            buttonsHtml += `<button class="page-btn ${i === meta.page ? 'active' : ''}" onclick="window.${onPageChange.name}(${i})">${i}</button>`;
        }
        
        if (meta.page < meta.total_pages - 2) buttonsHtml += `<span class="page-dots">...</span>`;
        buttonsHtml += `<button class="page-btn ${meta.total_pages === meta.page ? 'active' : ''}" onclick="window.${onPageChange.name}(${meta.total_pages})">${meta.total_pages}</button>`;
    }

    // Botón Siguiente
    buttonsHtml += `<button class="page-btn" ${meta.page >= meta.total_pages ? 'disabled' : ''} onclick="window.${onPageChange.name}(${meta.page + 1})"><i data-lucide="chevron-right" style="width:14px;"></i></button>`;

    container.innerHTML = `
        <div class="pagination-info">Mostrando <span>${meta.data_count || meta.limit}</span> de <span>${meta.total}</span> registros</div>
        <div class="pagination-controls">
            ${buttonsHtml}
        </div>
    `;
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
