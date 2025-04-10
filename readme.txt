=== InvBit Credits ===
Contributors: invbit
Tags: credits, page, shortcode
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate a design web page with a customizable shortcode.

== Description ==

WordPress plugin to generate a "Diseño Web" page via shortcode with two columns:
* Left: Custom content from admin panel (title, description, features)
* Right: Fixed company information

The plugin now supports automatic updates from GitHub repository.

== Installation ==

1. Upload plugin files to `/wp-content/plugins/invbit-credits/`
2. Activate plugin in WordPress
3. Go to 'Tools > Diseño Web' menu to configure
4. Click 'Create Diseño Web Page'
5. Configure your GitHub repository for automatic updates

== Changelog ==

= 1.0.2 =
* Agregado sistema de actualizaciones automáticas desde GitHub
* Incluida interfaz para configurar el repositorio de actualizaciones
* Añadida funcionalidad para comprobar actualizaciones manualmente
* Mejorado el sistema de verificación de versiones

= 1.0.1 =
* Mejorado el sistema de permisos y roles
* Arreglados problemas de seguridad de validación de datos
* Reforzado el escapado de datos de salida
* Verificación adecuada de nonce y CSRF
* Corrección de problemas de XSS potenciales
* Validación mejorada de archivos y recursos

= 1.0.0 =
* Initial release 