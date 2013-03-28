Color-Style-Options
===================

Easily allow for per-post custom styles. Admin creates the rules, writers can just pick colors.

Screencast*: https://www.youtube.com/watch?v=mjlelOvAUOs

*it's from a slightly earlier development version

To enable on pages (or other post types)
----------------------------------------
```php
add_filter( 'cso_screens', 'enable_cso_on_pages' );
function enable_cso_on_pages( $screens ) {
	$screens[] = 'page';
	return $screens;
}
```