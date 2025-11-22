// Configuración global para selectores de hora
function configurarTimepickers() {
    flatpickr('.hora-picker', {
        enableTime: true,
        noCalendar: true,
        dateFormat: "h:i K",
        time_24hr: false,
        locale: "es",
        minuteIncrement: 15,
        defaultHour: 7,
        minTime: "07:00",
        maxTime: "16:00",
        disable: [
            function(date) {
                const hours = date.getHours();
                return (hours < 7 || hours >= 16);
            }
        ],
        onOpen: function() {
            const container = this.calendarContainer;
            if (!container.querySelector('.range-tooltip')) {
                const tooltip = document.createElement('div');
                tooltip.className = 'range-tooltip';
                tooltip.innerHTML = 'Horario permitido: 7:00 AM - 4:00 PM';
                container.prepend(tooltip);
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates[0]) {
                const hour = selectedDates[0].getHours();
                if (hour < 7 || hour >= 16) {
                    instance.setDate(null);
                    Swal.fire({
                        title: 'Hora no permitida',
                        text: 'Por favor seleccione entre 7:00 AM y 4:00 PM',
                        icon: 'warning',
                        confirmButtonColor: '#c90000'
                    });
                }
            }
        },
        onReady: function() {
            if(/Android|webOS|iPhone|iPad/i.test(navigator.userAgent)) {
                this.mobileInput.setAttribute('step', '1800');
            }
        }
    });
}