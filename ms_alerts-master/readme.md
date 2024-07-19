## Requisitos

- PHP >= 8.1
- Composer
- MySQL o cualquier otra base de datos soportada por Laravel

## Instalación

En este proyecto, utilizamos Domain-Driven Design (DDD) como enfoque principal para el desarrollo. DDD nos permite centrarnos en el dominio del negocio y su lógica fundamental, asegurando que nuestro código refleje fielmente las reglas y procesos del negocio. Mediante la creación de modelos de dominio ricos y bien definidos, podemos gestionar la complejidad inherente a las aplicaciones empresariales y mejorar la mantenibilidad del código. Además, DDD facilita una comunicación clara y efectiva entre los desarrolladores y los expertos del dominio, promoviendo una comprensión compartida y una colaboración más eficiente. Este enfoque estructurado nos ayuda a construir software que no solo cumple con los requisitos funcionales, sino que también es flexible y escalable para futuros cambios y ampliaciones.


### Instalar las dependencias

```sh
composer install
```

Copia el archivo .env.example y renómbralo a .env.

```sh
cp .env.example .env
```

### Levantar el proyecto usando Docker
Si prefieres usar Docker, sigue estos pasos para levantar el entorno de desarrollo.

### Construir y levantar los contenedores
Asegúrate de tener Docker y Docker Compose instalados. Luego, ejecuta:


```sh
docker-compose up -d
```

## Ejemplos de uso

### Procesar un evento

Para que una alerta se cree se tiene que dar una serie de eventos. Para ello lo que se hace es pasar a todo evento que llega por un caso de uso que se llama ProcessJob, que será el encargado de analizar el evento que ha llegado junto con los datos asociados a ese evento para  crear o actualizar alertas dentro del sistema.

```php
use Core\Alerts\Application\Process\ProcessJob;
use Core\Alerts\Application\Get\GetAllAlertsByService;

$service_id = 5360070;

$event = [
  "action" => "skipped_stops_with_bookings",
  "more_info" => [
    "stop_id" => 570576,
    "stop_name" => "R. João Robalo, 470 - Jardim São Bento Novo, São Paulo - SP, 05881-000, Brasil",
    "stop_position" => 3
  ],
  "service_id" => $service_id
]

// Using the dependency injection 

$processor = app()->make(ProcessJob::class);

if ($processor->__invoke($event)) {
    echo "The event was processed and an alert was created or modified.";
} else {
    echo "No alert was created."
}


// Get all alerts from the service
$getAllAlertsByService = app()->make(GetAllAlertsByService::class);

$alerts = $getAllAlertsByService->__invoke($service_id);

```




### Licencia
Este proyecto está licenciado bajo la licencia MIT. Consulta el archivo LICENSE para obtener más información.

### Clonar el repositorio

```sh
git clone https://github.com/busup/ms_alerts.git
cd ms_alerts