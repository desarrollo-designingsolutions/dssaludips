<script>
import IdleJs from 'idle-js';

export default {
    name: 'App',
    methods: {
        async checkSession() {
            // try {
            //     const response = await axios.get('/api/check-session', {
            //         headers: {
            //             Authorization: `Bearer ${localStorage.getItem('token')}`,
            //         },
            //     });
            //     if (response.data.status === 'inactive') {
            //         this.logout();
            //     }
            // } catch (error) {
            //     if (error.response && error.response.status === 401) {
            //         this.logout();
            //     }
            // }
        },
        logout() {
            // localStorage.removeItem('token');
            // this.$router.push('/login');
            // // Opcional: Mostrar un mensaje al usuario
            // alert('Tu sesión ha expirado debido a inactividad.');

        },
    },
    mounted() {
        // Configurar detección de inactividad
        const idle = new IdleJs({
            idle: 5 * 1000, // 15 minutos en milisegundos
            events: ['mousemove', 'keydown', 'mousedown', 'touchstart'], // Eventos que reinician el temporizador
            onIdle: () => {

                this.checkSession();
            },
        });
        idle.start();

        // También puedes verificar periódicamente la sesión
        setInterval(() => {

            this.checkSession();
        }, 5 * 1000); // Cada 5 minutos
    },
};
</script>
