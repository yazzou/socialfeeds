# Socialfeed

## Introduction

- Socialfeed module provides the user to fetch the data from their respective
  Facebook, Twitter, and Instagram profiles and then display them accordingly as
  per their requirement using the Drupal block system.

- Facebook APIs will allow you to display particular post types, pictures,
  videos of your posts also the date of your post with provision to provide
  several counts.

- Instagram APIs will allow you to display pictures from your Instagram profile,
  it provides several counts to be displayed, and you can also offer the post
  link.

- Twitter APIs will allow you to get the latest tweets. This module supports
  both Twitter API v2 (using Bearer Token) and the older API v1.1 (using Consumer Keys and Access Tokens).
  API v2 is the recommended approach.

- This module is easy to install and use if the project page description or the
  README File is followed correctly.

- This module is highly recommended for both developers & non-developers since
  the default layout of the blocks are plain and in simple text hence if you're
  aware of working with CSS; then this module will work for you like a charm.

## Requirements

- PHP 7.4 and above.
- Composer (for managing dependencies like `abraham/twitteroauth`).
- A Twitter Developer Account and App.

## Installation

- Install, as usual, see [Installing modules](https://www.drupal.org/node/1897420) for further
  information.
- It's recommended to install via Composer to ensure all dependencies are downloaded:
  `composer require drupal/socialfeed`
- Enable the Socialfeed module on the Extend page (`/admin/modules`).

## Configuration

- Global configuration forms for each social media platform can be accessed at:
  Administration > Configuration > Web services > Social Feed (`/admin/config/services/socialfeed`).

### Twitter Configuration

The Twitter integration has been updated to support **Twitter API v2** (using a Bearer Token) and continues to support the legacy **API v1.1** (using Consumer API keys and Access Tokens). API v2 is preferred.

Go to the Twitter settings page: `/admin/config/services/socialfeed/twitter`.

**Authentication Options:**

You need to configure one of the following authentication methods:

1.  **Twitter API v2 (Recommended):**
    *   **Default Twitter Bearer Token (API v2):** Enter your Bearer Token obtained from your Twitter Developer App.
        *   *How to obtain:* Go to the [Twitter Developer Portal](https://developer.twitter.com), navigate to your Project, then your App. Under "Keys and tokens", you'll find your Bearer Token.
    *   **Twitter User ID (for API v2):** Enter the numerical User ID of the Twitter account whose tweets you want to display (e.g., `123456789`). This is *required* if you are using the Bearer Token.
        *   *How to obtain:* You can find a user's ID by looking at their profile URL or using online tools. For example, if a profile URL is `https://twitter.com/mytwitterhandle`, the User ID is different from `mytwitterhandle`.

2.  **Twitter API v1.1 (Legacy):**
    *   **Default Twitter API Key (Legacy v1.1):** Your app's "API Key" or "Consumer Key".
    *   **Default Twitter API Secret Key (Legacy v1.1):** Your app's "API Secret Key" or "Consumer Secret".
    *   **Default Twitter Access Token (Legacy v1.1):** The user-specific Access Token.
    *   **Default Twitter Access Token Secret (Legacy v1.1):** The user-specific Access Token Secret.
        *   *How to obtain:* These are found in your App's "Keys and tokens" section on the Twitter Developer Portal. You may need to generate them.

*If the Bearer Token (API v2) is provided, the API v1.1 legacy keys will be ignored.*

**Common Display Settings:**

*   **Default Tweet Count:** Number of tweets to display. For API v2, this value must be between 5 and 100.
*   **Cache Duration (minutes):** How long to store fetched tweets in the cache to reduce API calls and respect rate limits. Default is 15 minutes. This is highly recommended to prevent hitting Twitter's rate limits.
*   **Display Options:** Configure visibility of hashtags, date/time, date style (e.g., "time ago"), date format, text trimming, and teaser text.

**Using Twitter Blocks:**

Once configured, you can add Twitter feed blocks to your site via Drupal's block layout system (`/admin/structure/block`).

*   **Block-Level Overrides:** When configuring a Twitter block, you can choose to "Override global Twitter settings." This allows you to:
    *   Use a different Bearer Token (API v2) for that specific block.
    *   Use different API v1.1 keys/tokens for that specific block.
    *   Override the number of tweets to display for that block.
    *   If overriding, you must provide either a complete set of API v1.1 credentials or a Bearer Token for that block.
    *   The User ID and Cache Duration settings are currently global and not overridable per block.

**Important Notes for Twitter:**

*   **Rate Limits:** Twitter API has rate limits (e.g., 15 requests per 15 minutes for many user timeline endpoints on the Basic v2 plan). The caching mechanism in this module is designed to help manage this. Set a reasonable cache duration.
*   **API v2 vs. v1.1:** API v2 is the current standard. API v1.1 is considered legacy. This module attempts to provide a consistent output, but the raw data structure from API v2 is different. The module maps v2 data to a structure similar to v1.1 for theme compatibility, but custom themes might need adjustments if they relied on very specific v1.1 fields not covered in the mapping.
*   **Local Development:** Twitter's API (especially older versions) might have restrictions when used from a local development environment (localhost). Testing on a publicly accessible staging server is often more reliable.

### Facebook Configuration
[Instructions for Facebook - keep existing or update as needed]

### Instagram Configuration
[Instructions for Instagram - keep existing or update as needed]
