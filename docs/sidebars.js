/**
 * Creating a sidebar enables you to:
 - create an ordered group of docs
 - render a sidebar for each doc of that group
 - provide next/previous navigation
 */

/** @type {import('@docusaurus/plugin-content-docs').SidebarsConfig} */
const sidebars = {
  docsSidebar: [
    {
      type: 'doc',
      id: 'intro',
      label: 'Introduction',
    },
    {
      type: 'doc',
      id: 'getting-started',
      label: 'Getting Started',
    },
    {
      type: 'category',
      label: 'API Reference',
      items: [
        'api/rest-api',
        'api/graphql-api',
        'api/response-format',
      ],
    },
    {
      type: 'category',
      label: 'WordPress Hooks',
      items: [
        'hooks/filters',
        'hooks/actions',
      ],
    },
    {
      type: 'category',
      label: 'Guides',
      items: [
        'guides/block-filtering',
        'guides/custom-attributes',
        'guides/block-bindings',
        'guides/synced-patterns',
        'guides/draft-preview',
      ],
    },
    {
      type: 'category',
      label: 'Examples',
      items: [
        'examples/rest-api-examples',
        'examples/graphql-examples',
        'examples/react-renderer',
        'examples/filtering-blocks',
      ],
    },
    {
      type: 'category',
      label: 'Advanced',
      items: [
        'advanced/block-attribute-sources',
        'advanced/performance',
        'advanced/caching',
        'advanced/troubleshooting',
      ],
    },
  ],
};

module.exports = sidebars;
