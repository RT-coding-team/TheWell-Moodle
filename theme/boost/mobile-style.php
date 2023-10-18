<?php
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
$brandColor = get_config('theme_boost', 'brandcolor');
$greyColor = '#E7E9EB';
$blueColor = '#96D2DC';
$purpleColor = '#CDCBE6';
$ultraColor = '#0F009B';
header("Content-type: text/css");
?>
/* 3.5 styles */

/** Header color */
.toolbar-background-md,
.toolbar-background-ios,
.toolbar-background-wp {
  background-color: <?php echo $brandColor; ?>;
}

.tabs-md .tabbar, .tabs-ios .tabbar {
    background-color: <?php echo $brandColor; ?>;
}
.tabs-md .tab-button .tab-button-icon,
.tabs-md .core-tabs-bar a.tab-slide .tab-button-icon,
.core-tabs-bar .tabs-md a.tab-slide .tab-button-icon,
.tabs-ios .tab-button .tab-button-icon,
.tabs-md .core-tabs-bar a.tab-slide .tab-button-icon,
.core-tabs-bar .tabs-ios a.tab-slide .tab-button-icon,
.tabs-wp .tab-button .tab-button-icon,
.tabs-wp .core-tabs-bar a.tab-slide .tab-button-icon,
.core-tabs-bar .tabs-wp a.tab-slide .tab-button-icon {
  color: <?php echo $purpleColor; ?> !important;
}
.core-tabs-bar .slides a.tab-slide {
  color: <?php echo $purpleColor; ?> !important;
  border-bottom-color: <?php echo $purpleColor; ?> !important;
}

/* Bottom tabs selected color */
.tabs-md .tab-button[aria-selected=true] .tab-button-icon,
.tabs-md .core-tabs-bar a[aria-selected=true].tab-slide .tab-button-icon,
.core-tabs-bar .tabs-md a[aria-selected=true].tab-slide .tab-button-icon,
.tabs-ios .tab-button[aria-selected=true] .tab-button-icon,
.tabs-md .core-tabs-bar a[aria-selected=true].tab-slide .tab-button-icon,
.core-tabs-bar .tabs-ios a[aria-selected=true].tab-slide .tab-button-icon,
.tabs-wp .tab-button[aria-selected=true] .tab-button-icon,
.tabs-wp .core-tabs-bar a[aria-selected=true].tab-slide .tab-button-icon,
.core-tabs-bar .tabs-wp a[aria-selected=true].tab-slide .tab-button-icon {
  color: #FFFFFF !important;
}

ion-app.app-root.md .button-md-light,
ion-app.app-root.ios .button-ios-light {
    color: <?php echo $brandColor; ?> !important;
}

/* Tabs color*/
ion-app.app-root .core-tabs-bar .tab-slide[aria-selected=true],
.core-tabs-bar .slides a.tab-slide[aria-selected=true] {
  color: <?php echo $brandColor; ?> !important;
  border-bottom-color: <?php echo $brandColor; ?> !important;
}

ion-app.app-root addon-calendar-calendar .addon-calendar-day.today .addon-calendar-day-number span {
    background-color: <?php echo $brandColor; ?> !important;
}

/* Select boxes on home page. */
ion-app.app-root ion-select.core-button-select, ion-app.app-root .core-button-select,
ion-app.app-root ion-select.core-button-select .select-icon .select-icon-inner, ion-app.app-root .core-button-select .select-icon .select-icon-inner,
.item-radio-checked.item-md ion-label,
.item-radio-checked.item-ios ion-label,
.item-radio-checked.item-md ion-label {
    color: <?php echo $brandColor; ?> !important;
}

.radio-md .radio-icon,
.radio-ios .radio-icon {
    border-color: <?php echo $purpleColor; ?> !important;
}
.radio-md .radio-checked,
.radio-ios .radio-checked {
    border-color: <?php echo $brandColor; ?> !important;
}

.radio-md .radio-checked .radio-inner,
.radio-ios .radio-checked .radio-inner {
    background-color: <?php echo $brandColor; ?> !important;
}

.toggle-md .toggle-icon,
.toggle-ios .toggle-icon {
    background-color: <?php echo $greyColor; ?> !important;
}

.toggle-md .toggle-inner,
.toggle-ios .toggle-inner {
    background-color: <?php echo $greyColor; ?> !important;
}

.toggle-md.toggle-checked .toggle-inner,
.toggle-ios.toggle-checked .toggle-inner {
    background-color: <?php echo $brandColor; ?> !important;
}

.toggle-md.toggle-checked .toggle-icon,
.toggle-ios.toggle-checked .toggle-icon {
    background-color: <?php echo $purpleColor; ?> !important;
}

/* Loading spinner */
.spinner-crescent circle,
.spinner-md-crescent circle,
.spinner-ios-crescent circle,
.refresher-refreshing .spinner-crescent circle,
.refresher-refreshing .spinner-md-crescent circle,
.refresher-refreshing .spinner-ios-crescent circle,
.spinner circle,
.spinner line {
    stroke: <?php echo $brandColor; ?>;
}

.refresher-pulling-icon .icon,
.refresher-pulling-icon .icon-md,
.refresher-pulling-icon .icon-ios {
    color: <?php echo $brandColor; ?> !important;
}

/* Buttons */
.button-md,
.button-ios,
.button-wp,
.fab-ios,
.fab-md,
.fab-wp {
  background-color: <?php echo $brandColor; ?>;
}

.button-outline-md,
.button-outline-ios,
.button-outline-wp {
    border-color: <?php echo $brandColor; ?>;
    background-color: #FFFFFF !important;
    color: <?php echo $brandColor; ?>;
}

.button-clear-md,
.button-clear-ios,
.button-clear-wp {
  background-color: transparent;
  color: <?php echo $brandColor; ?>;
}

/* More icon colors */
page-core-mainmenu-more .ion-md-notifications,
page-core-mainmenu-more .ion-ios-notifications,
page-core-mainmenu-more .ion-md-notifications {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-stats,
page-core-mainmenu-more .ion-ios-stats,
page-core-mainmenu-more .ion-md-stats {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-map,
page-core-mainmenu-more .ion-ios-map,
page-core-mainmenu-more .ion-wp-map {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-folder,
page-core-mainmenu-more .ion-ios-folder,
page-core-mainmenu-more .ion-wp-folder {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-link,
page-core-mainmenu-more .ion-ios-link,
page-core-mainmenu-more .ion-wp-link {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-globe,
page-core-mainmenu-more .ion-ios-globe,
page-core-mainmenu-more .ion-wp-globe {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-qr-scanner,
page-core-mainmenu-more .ion-ios-qr-scanner,
page-core-mainmenu-more .ion-wp-qr-scanner {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-help-buoy,
page-core-mainmenu-more .ion-ios-help-buoy,
page-core-mainmenu-more .ion-wp-help-buoy {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-cog,
page-core-mainmenu-more .ion-ios-cog,
page-core-mainmenu-more .ion-wp-cog {
  color: <?php echo $brandColor; ?>;
}

page-core-mainmenu-more .ion-md-log-out,
page-core-mainmenu-more .ion-ios-log-out,
page-core-mainmenu-more .ion-wp-log-out {
  color: <?php echo $brandColor; ?>;
}

core-progress-bar progress .progress-bar-fallback span,
core-progress-bar progress[value]::-webkit-progress-value {
    background-color: <?php echo $brandColor; ?> !important;
}

.item-md.item-input.ng-valid.item-input-has-value:not(.input-has-focus):not(.item-input-has-focus) .item-inner,
.item-md.item-input.ng-valid.input-has-value:not(.input-has-focus):not(.item-input-has-focus) .item-inner {
  border-bottom-color: <?php echo $brandColor; ?> !important;
  -webkit-box-shadow: inset 0 -1px 0 0 <?php echo $brandColor; ?> !important;
  box-shadow: inset 0 -1px 0 0 <?php echo $brandColor; ?> !important;
}

.alert-md .alert-button {
    color: <?php echo $brandColor; ?> !important;
}

ion-app.app-root ion-action-sheet .action-sheet-wrapper .action-sheet-container .action-sheet-selected {
    color: <?php echo $brandColor; ?> !important;
}

ion-app.app-root ion-action-sheet .action-sheet-wrapper .action-sheet-container .action-sheet-button.action-sheet-cancel {
    color: <?php echo $blueColor; ?> !important;
}

.action-sheet-md .action-sheet-title {
    color: <?php echo $brandColor; ?> !important;
}

ion-app.app-root ion-select,
ion-app.app-root ion-select .select-icon .select-icon-inner {
    color: <?php echo $brandColor; ?> !important;
}

.picker-md .picker-button,
.picker-md .picker-button.activated,
.picker-ios .picker-button,
.picker-ios .picker-button.activated,
.picker-md .picker-opt.picker-opt-selected,
.picker-ios .picker-opt.picker-opt-selected {
    color: <?php echo $brandColor; ?> !important;
}

ion-app.app-root.md .button-md-light, ion-app.app-root.ios .button-ios-light {
    color: #FFFFFF !important;
}
