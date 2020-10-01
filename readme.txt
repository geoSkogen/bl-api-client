================================================================================
BrightLocal Client - Reviews - v 06.15.2020
================================================================================
================================================================================
CAVEATS & DISCLOSURES
================================================================================

This WordPress Plugin is built on the following OPEN SOURCE packages:

1) BrightLocal API Helper
  https://github.com/BrightLocal/BrightLocal-API-Helper

2) Guzzle PHP, which the BrightLocal Helper library Includes
  https://github.com/guzzle/guzzle

By installing this plugin, you are including these software packages in your
WordPress installation, on your website property.  City Ranked Media assumes
responsibility to update these dependency trees to their most recent versions
with each succeeding update of this plugin, and to produce plugin updates per
City Ranked Media's own self-determined need.  City Ranked Media does not assume
responsibility for software vulnerabilities in the versions of these packages
included in the current version 06.15.2020 of this plugin. Vulnerabilities
fitting the preceding description should be reported as issues to the above
GitHub repositories.

If you are using the most recent version of this plugin and believe the
dependencies still require updating for security reasons, follow these steps:


================================================================================
HOW YOU DO THIS - User Documentation
================================================================================

1) Page 1 : BrightLocal Client - Authentication - API Keys

  Sign Up for a BrightLocal Account:
  https://tools.brightlocal.com/seo-tools/admin/login?redirect_url=%252Fseo-tools%252Fadmin%252Fapi&redirect_source=bl_action

  Create a Developer API Key & Secret Here:
  https://tools.brightlocal.com/seo-tools/admin/api

2) Page 2 : BL Business Info - Crucial Considerations Filling Out The Form

  If you are A City Ranked Suite user, most of your form fields will pull info
  automatically from existing local business info already entered in CR Suite.

  The following fields will NOT populate automatically:

  A) PROFILE LINKS
  Enter the link for your GMB and/or Facebook profile to enable those Reviews.
  Leave it blank for that business location if none exists.

  B) TRACKING NUMBERS
  If you're using a tracking line for the primary phone number on your
  GMB listing, enter it in the field provided or the lookup will fail.

3) Page 3 : Authorize the Automated Tasks

  Plugin requires the user to manually confirm the Business Name entered in
  Locale #1 Business Name in order for WordPress to schedule the automated
  review import tasks. Choose a lookback window; this determines the scope of
  the API call and how often the review fetch tasks run if they are automated.

4) Page 4 : Deploy single review fetch API Calls in manual mode (optional)

  Please note that manual deployment currently results in a blocking call.
  This requires the API call execution to complete prior to pageload! It will
  take a minute.

5) Page 5 : BL Client Activity

  The Activity Page displays each status report from the last batch of API Calls.
  The Activity Page also displays a summary of recent reviews BrightLocal looked up.
  This is not what the user sees on the reviews page, i.e., it is not the
  shortcode output.

================================================================================

================================================================================
HOW YOU DO THIS - Developer Documentation
================================================================================

SHORTCODES

1) Reviews Module

  [bl_client_local_reviews]

  Returns pre-formatted review 'shrine' of all reviews, of all locales, from all
  directories, sorted descending by date, filtered for < 3 stars

2) Aggregate Rating

  [bl_client_agg_rating]

================================================================================
================================================================================
