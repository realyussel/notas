# ¿Porque construí y comencé a usar `notas`?

Paso mucho tiempo tratando de recordar cosas. De ahí nació `notas`; una herramienta con la cual organizar y buscar la información que necesito.

Además, disfruto bastante el proceso de creación de herramientas personalizadas como esta.

* Puede haber otras aplicaciones que hagan lo mismo, pero **`notas` es mía y funciona para mí**. 
* Tambien estoy consiente que sus capacidades no son de vanguardia: simplemente son lo suficientemente buenas para mi uso.
* He diseñado `notas` para:

   - [x] Mejorar mi flujo de trabajo,
   - [x] y Desarrollar el hábito el tomar notas cada día.

# `notas` es un borrador

`notas` se diseñó y construyó rápidamente en torno a algunas de mis necesidades, pero todavía tiene mucho margen de mejora y constantemente mi flujo de trabajo sigue creciendo y cambiando. Por eso llamo `v0` a la versión actual de `notas`.

# Instalación

* Se requiere al menos PHP 8.2 & Composer

```
git clone ...
composer install --no-dev
```

# Notas

* El archivo `composer.json` muestra las dependencias PHP del proyecto, entre ellas una base de datos JSON y librerías de consola y colores.

* El punto de entrada principal (`index.php`) configura PHP y carga la lista de usuarios desde `edit/data/users.json` para mostrarlos en la página inicial.

* El subdirectorio `edit/` contiene la funcionalidad de edición. Allí se manejan usuarios (clase `Login`), definición disponible desde las líneas iniciales de `edit/lib/login.class.php`.

* La clase `Jotter` se encarga de gestionar cuadernos de notas: crea directorios y guarda la configuración en `notebook.json`.

* La vista pública se implementa en `view/index.php`. Al instanciarse define constantes para rutas y carga configuraciones del cuaderno a mostrar.

* El directorio `view/deepwiki-themes/yussel` incluye la plantilla HTML del tema usado y un archivo `theme.json` con la lista de assets a cargar.

* Para listar los cuadernos de cada usuario existe `notebooks/index.php`, que recorre `edit/data/notebooks.json` y cuenta los apuntes por cuaderno.

# Resumen

El repositorio implementa una aplicación de apuntes en PHP.

* En la raíz hay un índice simple que enlaza al editor y a la lista de usuarios.

* El directorio `edit/` ofrece una interfaz de administración de usuarios y cuadernos, respaldada en archivos JSON.

* `view/` contiene el sistema de visualización basado en DeepWiki con soporte para Markdown (usando Parsedown) y temas personalizables.

* `notebooks/` genera páginas que muestran los cuadernos por usuario.
Para contribuir se recomienda familiarizarse con PHP moderno, manejo de archivos JSON y la librería Parsedown para extender el procesamiento de Markdown. Además, conviene explorar la arquitectura del tema en `view/deepwiki-themes` para personalizar la apariencia de la aplicación.