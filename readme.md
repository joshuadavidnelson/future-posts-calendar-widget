Future Posts Calendar Widget
============================

**Requires:** PHP v5.3.0, WordPress v3.0.0, Genesis v2.0

A calendar widget that displays future posts via a link to a page and shortcode. Place the shortcode `[future-posts-archive]` in a page, select that page for your widget and (optionally) a category to filter the results by. Include the category id in the shortcode via `[future-posts-archive category=10]` or `[future-posts-archive category="cake"]`.

**Note: This is a client-sponsored plugin, no support provided. Use at your own risk**


#### Changelog

##### 1.0.4 - Remove unnecessary error logging event.
 - Removes a `fpc_log_me` callout used for development.

##### 1.0.3 - Fix future month date.
 - Presents the next month link if the current page is a past archive, only shows "future" page link if it is the current month or another page, similar to the default calendar widget functionality.

##### 1.0.2 - Widget In-line Styles.
 - Add `width` in-line style to the widget to force equal width of td and th elements.

##### 1.0.1 - Default Class Consistency.
 - Add `widget_calendar` to class for theme styling consistency.

##### 1.0.0 - Initial Release.
