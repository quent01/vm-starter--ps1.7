/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
const webpack = require("webpack");
const path = require('path');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const FileSystem = require('fs');

const minimizers = [];
const plugins = [
  new FixStyleOnlyEntriesPlugin(),
  new MiniCssExtractPlugin({
    filename: '[name].css',
  }),
  new webpack.BannerPlugin({
      banner: FileSystem.readFileSync('./LICENSE.inc', 'utf-8'),
      entryOnly: true,
  })
];

const config = {
  entry: {
    'css/admin/global': './_dev/css/admin/global.scss',
    'css/front/checkout': './_dev/css/front/checkout.scss',
    'js/admin/account-settings': './_dev/js/admin/account-settings.js',
    'js/admin/help': './_dev/js/admin/help.js',
    'js/admin/selected-relay': './_dev/js/admin/selected-relay.js',
    'js/admin/labels-history': './_dev/js/admin/labels-history.js',
    'js/admin/orders': './_dev/js/admin/orders.js',
    'js/front/checkout/checkout-16-5steps': './_dev/js/front/checkout/checkout-16-5steps.js',
    'js/front/checkout/checkout-16-opc': './_dev/js/front/checkout/checkout-16-opc.js',
    'js/front/checkout/checkout-17': './_dev/js/front/checkout/checkout-17.js',
  },

  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, './views/'),
  },

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env'],
            },
          },
        ],
      },

      {
        test: /\.(s)?css$/,
        use: [
          {loader: MiniCssExtractPlugin.loader},
          {loader: 'css-loader'},
          {loader: 'postcss-loader'},
          {loader: 'sass-loader'},
        ],
      },

    ],
  },

  externals: {
    $: '$',
    jquery: 'jQuery',
  },

  plugins,

  optimization: {
    minimizer: minimizers,
  },

  resolve: {
    extensions: ['.js', '.scss', '.css'],
    alias: {
      '~': path.resolve(__dirname, './node_modules'),
      '$img_dir': path.resolve(__dirname, './views/img'),
    },
  },
};

module.exports = (env, argv) => {
  // Production specific settings
  if (argv.mode === 'production') {
    const terserPlugin = new TerserPlugin({
      cache: true,
      extractComments: false, // Remove comments except those containing @preserve|@license|@cc_on
      parallel: true,
      terserOptions: {
        drop_console: true,
        output : {
            comments: /^\**!|@preserve|@license|@cc_on/i // Preserve comments
        }
      },
    });

    config.optimization.minimizer.push(terserPlugin);
  }

  return config;
};
