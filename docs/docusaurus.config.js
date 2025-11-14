// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const {themes} = require('prism-react-renderer');
const lightCodeTheme = themes.github;
const darkCodeTheme = themes.dracula;

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'VIP Block Data API',
  tagline: 'Convert Gutenberg blocks into structured JSON for headless WordPress',
  favicon: 'img/favicon.ico',

  // Set the production url of your site here
  url: 'https://automattic.github.io',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/vip-block-data-api/',

  // GitHub pages deployment config.
  organizationName: 'Automattic',
  projectName: 'vip-block-data-api',

  onBrokenLinks: 'warn',
  onBrokenMarkdownLinks: 'warn',

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          routeBasePath: '/',
          sidebarPath: require.resolve('./sidebars.js'),
          editUrl: 'https://github.com/Automattic/vip-block-data-api/tree/trunk/docs/',
        },
        blog: false,
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      image: 'img/vip-block-data-api-social-card.jpg',
      navbar: {
        title: 'VIP Block Data API',
        logo: {
          alt: 'VIP Block Data API Logo',
          src: 'img/logo.svg',
        },
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'docsSidebar',
            position: 'left',
            label: 'Documentation',
          },
          {
            href: 'https://github.com/Automattic/vip-block-data-api',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Documentation',
            items: [
              {
                label: 'Getting Started',
                to: '/getting-started',
              },
              {
                label: 'REST API',
                to: '/api/rest-api',
              },
              {
                label: 'GraphQL API',
                to: '/api/graphql-api',
              },
            ],
          },
          {
            title: 'Community',
            items: [
              {
                label: 'WordPress VIP',
                href: 'https://wpvip.com/',
              },
              {
                label: 'GitHub Issues',
                href: 'https://github.com/Automattic/vip-block-data-api/issues',
              },
            ],
          },
          {
            title: 'More',
            items: [
              {
                label: 'GitHub',
                href: 'https://github.com/Automattic/vip-block-data-api',
              },
              {
                label: 'WordPress.org',
                href: 'https://wordpress.org/plugins/vip-block-data-api/',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Automattic, Inc. Built with Docusaurus.`,
      },
      prism: {
        theme: lightCodeTheme,
        darkTheme: darkCodeTheme,
        additionalLanguages: ['php', 'bash', 'json', 'graphql'],
      },
      algolia: {
        appId: 'YOUR_APP_ID',
        apiKey: 'YOUR_SEARCH_API_KEY',
        indexName: 'vip-block-data-api',
        contextualSearch: true,
      },
    }),
};

module.exports = config;
