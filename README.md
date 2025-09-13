# Speed Dial - WordPress Plugin

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

A nostalgic Nokia 3310-style phone dialer for WordPress that connects numbers to websites. Transform your site navigation into a retro mobile phone experience complete with DTMF tones and authentic Nokia interface.

**By [Ashraf Ali](https://ashrafali.net)**

*Inspired by Internet Phone Book's [Dial-a-Site](https://internetphonebook.net/)*

## 🎯 Features

- **Nokia 3310 Interface**: Authentic retro phone design with pixel-perfect CSS
- **DTMF Sound Effects**: Real dial tones using WebAudio API
- **Number Mapping**: Connect any phone number to any website URL
- **Multiple Display Options**: Shortcode, Gutenberg block, and classic widget
- **Admin Management**: Full CRUD interface for managing number mappings
- **CSV Import/Export**: Bulk manage your speed dial directory
- **REST API**: Modern API endpoints with AJAX fallbacks
- **Responsive Design**: Works on all devices and screen sizes
- **Accessibility**: Full keyboard support and ARIA labels
- **Internationalization**: Translation-ready with .pot file included

## 🎮 How It Works

1. **Admin Setup**: Add number-to-URL mappings in WordPress admin (e.g., "411" → "https://example.com")
2. **User Interaction**: Visitors see the Nokia phone interface on your page
3. **Dialing**: Users press number keys to dial (with authentic DTMF sounds)
4. **Connection**: Press "Call" to look up the number and get redirected to the mapped website
5. **Navigation**: Automatically redirects or shows a "Visit" button based on settings

## 📦 Installation

### From GitHub

1. Download the latest release from the [Releases page](https://github.com/nerveband/speed-dial/releases)
2. Upload the `speed-dial` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Speed Dial** in your WordPress admin to configure

### Manual Installation

```bash
cd wp-content/plugins/
git clone https://github.com/nerveband/speed-dial.git
cd speed-dial
```

### Using WP-CLI

```bash
wp plugin install https://github.com/nerveband/speed-dial/archive/main.zip --activate
```

## 🚀 Usage

### Shortcode

Add the Speed Dial dialer anywhere in your content:

```
[speed-dial]
```

With options:
```
[speed-dial digits="411" auto_focus="true"]
```

### Gutenberg Block

1. In the block editor, search for "Speed Dial"
2. Add the block to your page
3. Configure options in the block settings panel

### PHP Template

```php
<?php echo do_shortcode('[speed-dial]'); ?>
```

## ⚙️ Configuration

### Admin Panel

Navigate to **Speed Dial** in your WordPress admin menu:

- **Numbers**: View and manage all number mappings
- **Add New**: Create new number-to-website mappings
- **Import/Export**: Bulk manage via CSV
- **Settings**: Configure display and behavior options

### Number Mapping

Each number can be mapped to:
- **Title**: Display name for the website
- **URL**: Full website URL
- **Note**: Optional description
- **Active**: Enable/disable without deleting

### Settings Options

- **Connecting Text**: Message shown while connecting
- **Auto Redirect**: Automatically navigate after displaying result
- **Redirect Delay**: Milliseconds before auto-redirect
- **Sound Effects**: Enable/disable DTMF tones
- **Vibration**: Enable haptic feedback (mobile only)
- **Theme**: Choose between Nokia 3310 or Minimal

## 🎨 Themes

### Nokia 3310 (Default)
Authentic recreation of the classic Nokia 3310 interface with:
- Green LCD screen effect
- Pixel font (VT323)
- Realistic button design
- Speaker grille texture

### Minimal
Clean, modern design for contemporary websites

## 🔊 Sound Effects

The plugin uses WebAudio API to generate authentic DTMF (Dual-Tone Multi-Frequency) tones:

| Key | Low Freq | High Freq |
|-----|----------|-----------|
| 1   | 697 Hz   | 1209 Hz   |
| 2   | 697 Hz   | 1336 Hz   |
| 3   | 697 Hz   | 1477 Hz   |
| 4   | 770 Hz   | 1209 Hz   |
| 5   | 770 Hz   | 1336 Hz   |
| 6   | 770 Hz   | 1477 Hz   |
| 7   | 852 Hz   | 1209 Hz   |
| 8   | 852 Hz   | 1336 Hz   |
| 9   | 852 Hz   | 1477 Hz   |
| 0   | 941 Hz   | 1336 Hz   |

## 🔌 API Endpoints

### REST API

Base URL: `/wp-json/sd/v1/`

#### Lookup Number
```
GET /lookup?number=123
```

Response:
```json
{
  "found": true,
  "number": "123",
  "title": "Example Site",
  "url": "https://example.com/",
  "note": "Optional note"
}
```

#### Suggest Numbers (Optional)
```
GET /suggest?prefix=12&limit=5
```

### AJAX Fallback

For hosts that block REST API:
```javascript
jQuery.get(ajaxurl, {
  action: 'sd_lookup',
  number: '123',
  nonce: SDN.nonce
});
```

## 🛠️ Development

### Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10.1+
- Modern browser with JavaScript enabled

### Build Steps

```bash
# Clone the repository
git clone https://github.com/nerveband/speed-dial.git
cd speed-dial

# No build required - plugin is ready to use!
# All assets are pre-built and included

# To create a distribution package:
chmod +x package.sh
./package.sh

# Or manually:
zip -r speed-dial.zip . -x "*.git*" ".DS_Store" "*.distignore" "package.sh" "dist/*"
```

### Development Setup

1. Clone into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/nerveband/speed-dial.git
   ```

2. Activate the plugin in WordPress admin

3. Start developing! The plugin uses:
   - Vanilla JavaScript (no build step required)
   - WebAudio API for sound generation
   - Standard WordPress APIs

### Project Structure

```
speed-dial/
├── speed-dial.php       # Main plugin file
├── inc/                 # PHP classes
├── assets/              # CSS, JS, images
├── block/               # Gutenberg block
└── languages/           # Translations
```


## 🌍 Internationalization

The plugin is fully translatable. Translation files are located in the `languages/` directory.

To translate:
1. Use the `speed-dial.pot` file as a template
2. Create your translation files (e.g., `speed-dial-fr_FR.po`)
3. Compile to `.mo` format

## 🐛 Troubleshooting

### Common Issues

**Sounds not playing:**
- Check browser autoplay policies
- Ensure first user interaction has occurred
- Verify sound is enabled in settings

**REST API not working:**
- Check permalink settings (must not be "Plain")
- Verify `.htaccess` file is writable
- AJAX fallback will activate automatically

**Numbers not saving:**
- Check database table creation
- Verify write permissions
- Check PHP error logs

## 📝 CSV Format

### Import Format

```csv
number,title,url,note,is_active
411,Directory,https://example.com/directory,Main directory,1
911,Support,https://example.com/support,Get help,1
```

### Export Format

Same as import format, UTF-8 encoded with headers.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👏 Credits

- **Author**: [Ashraf Ali](https://ashrafali.net)
- **Inspiration**: Internet Phone Book's [Dial-a-Site](https://internetphonebook.net/)
- **Design**: Nokia 3310 classic phone interface
- **Font**: VT323 by Peter Hull
- **Sound**: WebAudio API for DTMF tone generation

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/nerveband/speed-dial/issues)
- **Author**: [https://ashrafali.net](https://ashrafali.net)

## 🔄 Changelog

### Version 1.0.0 (2024-01-12)
- Initial release
- Nokia 3310 theme
- Full admin interface
- REST API endpoints
- Gutenberg block support
- CSV import/export

---

Made with ❤️ by [Ashraf Ali](https://ashrafali.net)