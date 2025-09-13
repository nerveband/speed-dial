=== Speed Dial ===
Contributors: ashrafali
Donate link: https://ashrafali.net
Tags: navigation, nokia, phone, dialer, retro, shortcode, block
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Transform your WordPress navigation into a nostalgic Nokia 3310 phone dialer that connects numbers to websites. Inspired by Internet Phone Book's Dial-a-Site.

== Description ==

**Speed Dial** brings the nostalgic Nokia 3310 experience to WordPress! Create an interactive phone dialer that connects phone numbers to website URLs, complete with authentic DTMF tones and retro interface design.

Perfect for creative navigation, interactive directories, or adding a unique retro touch to your website.

Inspired by Internet Phone Book's [Dial-a-Site](https://internetphonebook.net/), this plugin recreates the classic experience of dialing numbers to navigate websites.

= Key Features =

* **Authentic Nokia 3310 Design** - Pixel-perfect recreation of the classic phone interface
* **DTMF Sound Effects** - Real dial tones using WebAudio API
* **Flexible Display Options** - Use as shortcode, Gutenberg block, or widget
* **Easy Number Management** - Simple admin interface for mapping numbers to URLs
* **CSV Import/Export** - Bulk manage your speed dial directory
* **Mobile Responsive** - Works perfectly on all devices
* **Accessibility Ready** - Full keyboard support and ARIA labels
* **Developer Friendly** - REST API, hooks, and filters for customization

= Use Cases =

* Creative website navigation
* Interactive business directories
* Fun 404 error pages
* Contact pages with a twist
* Restaurant table booking systems
* Event registration portals
* Gaming and entertainment sites
* Retro-themed websites

= How It Works =

1. Install and activate the plugin
2. Add number-to-website mappings in the admin panel
3. Place the dialer on any page using shortcode or block
4. Visitors dial numbers to navigate to different sites
5. Enjoy the nostalgic experience!

= Shortcode Example =

`[speed-dial]`

With options:
`[speed-dial digits="411" auto_focus="true"]`

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Modern browser with JavaScript enabled

== Installation ==

= From WordPress Admin =

1. Navigate to Plugins > Add New
2. Search for "Speed Dial"
3. Click "Install Now" and then "Activate"
4. Go to Speed Dial menu to configure

= Manual Installation =

1. Download the plugin zip file
2. Navigate to Plugins > Add New > Upload Plugin
3. Choose the downloaded file and click "Install Now"
4. Activate the plugin
5. Configure under Speed Dial menu

= Via FTP =

1. Download and extract the plugin zip file
2. Upload the `speed-dial` folder to `/wp-content/plugins/`
3. Activate through the Plugins menu in WordPress
4. Configure under Speed Dial menu

== Frequently Asked Questions ==

= How do I add the dialer to my page? =

You can add the dialer using:
- **Shortcode**: `[speed-dial]` in any post or page
- **Block**: Search for "Speed Dial" in the block editor
- **Widget**: Add "Speed Dial" widget to any widget area
- **PHP**: `<?php echo do_shortcode('[speed-dial]'); ?>` in templates

= Can I customize the appearance? =

Yes! The plugin includes:
- Nokia 3310 theme (default)
- Minimal theme option
- CSS classes for custom styling
- Filter hooks for advanced customization

= Do the sounds work on mobile? =

Yes, sounds work on mobile devices after the first user interaction. This is due to browser autoplay policies. The plugin handles this automatically.

= Can I import existing number mappings? =

Yes! Use the CSV import feature to bulk upload mappings. Format:
`number,title,url,note,is_active`

= Is it accessible? =

Yes! The plugin includes:
- Full keyboard navigation
- ARIA labels and live regions
- Screen reader support
- High contrast compatibility

= Can I use it for international numbers? =

The plugin accepts any numeric sequence up to 16 digits. You can map any number format to any URL.

= Does it work with page builders? =

Yes! The shortcode works with all major page builders including Elementor, Divi, Beaver Builder, and others.

= How do I disable sounds? =

Go to Speed Dial > Settings and uncheck "Enable sound effects"

= Can I have multiple dialers on one page? =

Yes, you can add multiple instances of the shortcode or block on the same page.

= Is there an API? =

Yes! The plugin provides REST API endpoints for developers:
- `/wp-json/sd/v1/lookup` - Look up a number
- `/wp-json/sd/v1/suggest` - Get number suggestions

== Screenshots ==

1. Nokia 3310 style dialer on frontend
2. Admin panel - number management
3. Add/Edit number mapping screen
4. Settings configuration page
5. CSV import/export interface
6. Gutenberg block in action
7. Mobile responsive view
8. Minimal theme option
9. Dialing animation with sounds
10. Search result display

== Changelog ==

= 1.0.0 - 2024-01-12 =
* Initial release
* Nokia 3310 authentic interface
* DTMF sound generation
* Complete admin management system
* REST API endpoints
* Gutenberg block support
* CSV import/export functionality
* Shortcode and widget support
* Responsive design
* Accessibility features
* Internationalization ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of Speed Dial plugin. Install to add nostalgic Nokia phone navigation to your WordPress site!

== Additional Information ==

= Support =

For support, please visit our [GitHub repository](https://github.com/nerveband/speed-dial) and open an issue.

= Contributing =

We welcome contributions! Visit our [GitHub repository](https://github.com/nerveband/speed-dial) to contribute.

= Privacy =

This plugin:
- Does not collect personal data
- Does not use external services
- Does not set cookies
- Stores mappings in your WordPress database only

= Credits =

* Nokia 3310 design inspiration
* VT323 font by Peter Hull
* WebAudio API for sound generation
* WordPress community

== Developer Information ==

= Hooks and Filters =

**Actions:**
* `speed_dial_call` - Fired when a number is dialed
* `speed_dial_event` - General event logging

**Filters:**
* `speed_dial_max_digits` - Maximum allowed digits (default: 16)
* `speed_dial_themes` - Available theme options
* `speed_dial_lookup_response` - Modify lookup response
* `speed_dial_manage_capability` - Required capability (default: manage_options)

= REST API =

Base: `/wp-json/sd/v1/`

**Endpoints:**
* `GET /lookup?number=123` - Look up a number
* `GET /suggest?prefix=12&limit=5` - Get suggestions

= Database =

Custom table: `{prefix}_speed_dial_map`

= Uninstall =

The plugin cleanly removes all data on uninstall unless "Keep data on uninstall" is enabled in settings.
