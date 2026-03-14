# Lunara

The repository for the Lunara film website, featuring reviews and a bespoke Oscars database.

## Installation into WordPress

Lunara is distributed as a WordPress theme, and may include one or more custom plugins.
To deploy this repository into a WordPress site, use the standard WordPress directory
layout under `wp-content`:

1. Clone or download this repository somewhere on your server or development machine
   (it does **not** need to live inside your WordPress directory):

   ```bash
   git clone https://github.com/your-org/lunara.git
   ```

2. Identify the Lunara **theme** directory in this repository. This is the folder that
   contains a `style.css` file with a WordPress `Theme Name` header (for example,
   a folder named `lunara` or similar).

3. Copy or symlink that theme directory into your WordPress installation under:

   ```text
   /path/to/wordpress/wp-content/themes/<lunara-theme-folder>/
   ```

   After this step, the Lunara theme folder should be a direct child of
   `wp-content/themes/`.

4. If this repository includes one or more custom **plugins** (each plugin is typically
   a folder containing a main PHP file with a WordPress plugin header), copy or symlink
   each plugin folder into:

   ```text
   /path/to/wordpress/wp-content/plugins/<plugin-folder>/
   ```

   After this step, each Lunara plugin folder should be a direct child of
   `wp-content/plugins/`.

5. Log in to your WordPress admin dashboard:

   - Go to **Appearance → Themes** and activate the **Lunara** theme.
   - Go to **Plugins → Installed Plugins** and activate any Lunara plugins you copied
     into `wp-content/plugins/`.

Once these steps are complete, your WordPress site will use the Lunara theme and
any associated plugins to power the film reviews and Oscars database functionality.
