# VIP Block Data API Documentation

This directory contains the documentation website for the VIP Block Data API plugin, built with [Docusaurus](https://docusaurus.io/).

## Development

### Prerequisites

- Node.js 18.0 or higher
- npm or yarn

### Installation

```bash
npm install
```

### Local Development

Start the development server:

```bash
npm start
```

This command starts a local development server and opens up a browser window. Most changes are reflected live without having to restart the server.

### Build

Generate static content for production:

```bash
npm run build
```

This command generates static content into the `build` directory and can be served using any static contents hosting service.

### Serve Built Site

Test the production build locally:

```bash
npm run serve
```

### Deployment

The documentation is automatically deployed to GitHub Pages when changes are pushed to the `trunk` branch. The deployment is handled by GitHub Actions (see `.github/workflows/deploy-docs.yml`).

## Documentation Structure

```
docs/
├── docs/                    # Documentation pages
│   ├── intro.md            # Introduction and overview
│   ├── getting-started.md  # Installation and setup guide
│   ├── api/                # API reference documentation
│   │   ├── rest-api.md     # REST API reference
│   │   ├── graphql-api.md  # GraphQL API reference
│   │   └── response-format.md # Response format details
│   ├── hooks/              # WordPress hooks documentation
│   │   ├── filters.md      # Filters reference
│   │   └── actions.md      # Actions reference
│   ├── guides/             # How-to guides
│   │   ├── block-filtering.md
│   │   ├── custom-attributes.md
│   │   ├── block-bindings.md
│   │   └── synced-patterns.md
│   ├── examples/           # Code examples
│   │   ├── rest-api-examples.md
│   │   ├── graphql-examples.md
│   │   ├── react-renderer.md
│   │   └── filtering-blocks.md
│   └── advanced/           # Advanced topics
│       ├── block-attribute-sources.md
│       ├── performance.md
│       ├── caching.md
│       └── troubleshooting.md
├── src/                    # Custom components and styles
│   └── css/
│       └── custom.css      # Custom CSS
├── static/                 # Static files
│   └── img/               # Images
├── docusaurus.config.js    # Docusaurus configuration
├── sidebars.js            # Sidebar navigation
└── package.json           # Dependencies

## Writing Documentation

### Creating a New Page

1. Create a new Markdown file in the appropriate directory under `docs/`
2. Add frontmatter at the top of the file:

```markdown
---
sidebar_position: 1
title: Page Title
---

# Page Title

Your content here...
```

3. The page will automatically be added to the documentation site

### Code Blocks

Use fenced code blocks with language identifiers:

```markdown
```javascript
function example() {
  return 'Hello World';
}
\```
```

Supported languages include: `javascript`, `typescript`, `php`, `bash`, `json`, `graphql`, `jsx`, `tsx`, and more.

### Callouts

Use callouts for important information:

```markdown
:::info
This is informational content.
:::

:::warning
This is a warning.
:::

:::danger
This is dangerous!
:::

:::tip
This is a helpful tip.
:::
```

### Links

Internal links (to other docs pages):

```markdown
[Link text](./other-page.md)
[Link text](../category/page.md)
```

External links:

```markdown
[WordPress VIP](https://wpvip.com/)
```

## Styling

Custom styles are defined in `src/css/custom.css`. The documentation follows the WordPress VIP design system.

### Color Variables

```css
--ifm-color-primary: #0675C4;
--ifm-color-primary-dark: #0569b1;
/* See custom.css for complete list */
```

## Contributing

When contributing to the documentation:

1. Ensure your changes follow the existing style and structure
2. Test locally with `npm start` before committing
3. Check that the build succeeds with `npm run build`
4. Keep examples practical and well-commented
5. Update the sidebar configuration in `sidebars.js` if adding new sections

## Resources

- [Docusaurus Documentation](https://docusaurus.io/docs)
- [Markdown Guide](https://www.markdownguide.org/)
- [WordPress VIP](https://wpvip.com/)
- [Plugin Repository](https://github.com/Automattic/vip-block-data-api)

## License

The documentation is part of the VIP Block Data API plugin and is licensed under GPL-3.0.
