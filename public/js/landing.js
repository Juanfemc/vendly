document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('landing-mobile-menu');

    if (!menuToggle) {
        return;
    }

    document.querySelectorAll('[data-menu-close]').forEach((item) => {
        item.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    });

    const cycleOptions = document.querySelectorAll('[data-billing-cycle]');
    const cyclePicker = document.querySelector('[data-billing-config]');
    const cycleConfig = JSON.parse(cyclePicker?.dataset.billingConfig || '{}');

    const formatCop = (amount) => `$${new Intl.NumberFormat('es-CO').format(amount)}`;

    const updatePlanPrices = (cycle) => {
        document.querySelectorAll('[data-plan-prices]').forEach((priceElement) => {
            const prices = JSON.parse(priceElement.dataset.planPrices || '{}');
            const total = Number(prices[cycle] || 0);
            const months = Number(cycleConfig[cycle]?.months || 1);
            const monthlyEquivalent = months > 1 ? Math.round(total / months) : total;
            const value = priceElement.querySelector('.plan-price-value');
            const period = priceElement.querySelector('.plan-price-period');
            const note = priceElement.parentElement.querySelector('[data-plan-price-note]');
            const isFree = total === 0;

            value.textContent = isFree ? 'Gratis' : formatCop(total);
            period.textContent = isFree ? '' : (cycleConfig[cycle]?.period || '/mes');
            note.textContent = isFree || !cycleConfig[cycle]?.discount
                ? ''
                : `Equivale a ${formatCop(monthlyEquivalent)} al mes · ahorra ${cycleConfig[cycle].discount}%`;
        });

        document.querySelectorAll('[data-plan-link]').forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('plan', link.dataset.planKey || 'premium');
            url.searchParams.set('billing_cycle', cycle);
            link.href = url.toString();
        });
    };

    cycleOptions.forEach((option) => {
        option.addEventListener('click', () => {
            const cycle = option.dataset.billingCycle;
            cycleOptions.forEach((item) => {
                const active = item === option;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            updatePlanPrices(cycle);
        });
    });

    if (cycleOptions.length > 0) {
        updatePlanPrices(document.querySelector('[data-billing-cycle].is-active')?.dataset.billingCycle || 'monthly');
    }
});
