# RamSetu Financial Services — Website

Custom WordPress theme and lead-management plugin for **RamSetu Financial Services**, live at **[ramsetufin.com](https://ramsetufin.com)** (October 2026).

Built by Chandan Gowda VG ([@11-Chandangowda](https://github.com/11-Chandangowda)), Liveon Technolabs.

| Folder | Version | What it is |
|---|---|---|
| `ramsetu-theme` | 1.12.0 | Classic PHP theme with every page of the site |
| `ramsetu-leads` | 1.4.1 | Lead capture and management plugin |

## Theme: `ramsetu-theme`

- **Pages:**
  - Home, with a product slider (one slide per loan type) and founder message
  - About Us: vision, mission, leadership, advisors, supporters & partners, presence and roadmap
  - Product pages for RamAffordable and SetuEmerging (Prime)
  - Branch Locator, Contact Us, Become a Partner, Apply Now and Policies
- **Editable content:** almost every text and photo can be changed in **Appearance → Customize**, with defaults in `inc/defaults.php`. Lists are one item per line.
- **Safe upgrades:** versioned migrations in `inc/setup.php` update texts only if the site owner hasn't edited them. They also rebuild the menu only if it is still the theme-made one.
- **Performance and accessibility:** WebP photos with `srcset`, no page builder, no jQuery, mobile-first CSS, keyboard-friendly menus and a reduced-motion slider.

## Plugin: `ramsetu-leads`

- **Public forms:** Apply Now, Request a Call Back and Become a Partner, available as shortcodes:
  - `[ramsetu_apply_form]`
  - `[ramsetu_callback_form]`
  - `[ramsetu_partner_form]`
- **Spam and abuse protection:** honeypot, timing check and per-IP rate limits.
- **Validation:**
  - Indian mobile numbers and PAN, checked in the browser and on the server.
  - Aadhaar is optional and checked with the Verhoeff checksum. It is stored **AES-256-GCM encrypted**, shown masked (`XXXX XXXX 1234`), and each time an admin reveals the full number it is logged.
- **Admin dashboard:**
  - Leads, call-backs and partner registrations
  - Status, assignment and remarks, with an activity history
  - Filters and CSV export (protected against formula injection)
  - Branches, Team & Advisors, Lending Partners
- **Partner & Employee portal:**
  - Partners register and RamSetu approves them; each gets a unique Partner ID and a set-password email.
  - Partners submit and track their own leads.
- **Email alerts:** an email for every new submission.

## Install

1. WordPress 6.2+ and PHP 7.4+.
2. Zip each folder, for example `zip -r ramsetu-theme.zip ramsetu-theme`.
3. **Plugins → Add New → Upload** `ramsetu-leads.zip`, then activate it.
4. **Appearance → Themes → Add New → Upload** `ramsetu-theme.zip`, then activate it. Pages, menu and sample content are created on first activation.
5. Set the notification email in **RamSetu Leads → Settings**, and set the content in **Appearance → Customize**.

## Tech

PHP (WordPress APIs, `$wpdb` with prepared statements, nonces, capability checks), vanilla JavaScript, CSS custom properties and OpenSSL. Hosted on WordPress.com, with the domain at GoDaddy (connected via Domain Connect).

---

Photos, logo and text content © RamSetu Financial Services Private Limited. Shared here as a portfolio project.
