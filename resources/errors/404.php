<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <link rel="icon" type="image/png" sizes="16x16" href="favicon.ico">
    <style>
        :root {
            --primary-dark: #0f1829;
            --secondary-dark: #404243;
            --primary-light: #fff;
            --secondary-light: #e8e8e8;
            --muted: #94a4b7;
        }

        body {
            font-size: 16px;
            color: var(--secondary-dark);
            background-color: var(--primary-dark);
        }

        /*
        ! tailwindcss v3.3.5 | MIT License | https://tailwindcss.com
        */

        /*
		1. Prevent padding and border from affecting element width. (https://github.com/mozdevs/cssremedy/issues/4)
		2. Allow adding a border to an element by just adding a border-width. (https://github.com/tailwindcss/tailwindcss/pull/116)
		*/

        *,
        ::before,
        ::after {
            box-sizing: border-box;
            /* 1 */
            border-width: 0;
            /* 2 */
            border-style: solid;
            /* 2 */
            border-color: #e5e7eb;
            /* 2 */
        }

        ::before,
        ::after {
            --tw-content: '';
        }

        /*
		1. Use a consistent sensible line-height in all browsers.
		2. Prevent adjustments of font size after orientation changes in iOS.
		3. Use a more readable tab size.
		4. Use the user's configured `sans` font-family by default.
		5. Use the user's configured `sans` font-feature-settings by default.
		6. Use the user's configured `sans` font-variation-settings by default.
		*/

        html {
            line-height: 1.5;
            /* 1 */
            -webkit-text-size-adjust: 100%;
            /* 2 */
            -moz-tab-size: 4;
            /* 3 */
            tab-size: 4;
            /* 3 */
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            /* 4 */
            font-feature-settings: normal;
            /* 5 */
            font-variation-settings: normal;
            /* 6 */
        }

        /*
		1. Remove the margin in all browsers.
		2. Inherit line-height from `html` so users can set them as a class directly on the `html` element.
		*/

        body {
            margin: 0;
            /* 1 */
            line-height: inherit;
            /* 2 */
        }

        /*
		1. Add the correct height in Firefox.
		2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
		3. Ensure horizontal rules are visible by default.
		*/

        hr {
            height: 0;
            /* 1 */
            color: inherit;
            /* 2 */
            border-top-width: 1px;
            /* 3 */
        }

        /*
		Add the correct text decoration in Chrome, Edge, and Safari.
		*/

        abbr:where([title]) {
            -webkit-text-decoration: underline dotted;
            text-decoration: underline dotted;
        }

        /*
		Remove the default font size and weight for headings.
		*/

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-size: inherit;
            font-weight: inherit;
        }

        /*
		Reset links to optimize for opt-in styling instead of opt-out.
		*/

        a {
            color: inherit;
            text-decoration: inherit;
        }

        /*
		Add the correct font weight in Edge and Safari.
		*/

        b,
        strong {
            font-weight: bolder;
        }

        /*
		1. Use the user's configured `mono` font family by default.
		2. Correct the odd `em` font sizing in all browsers.
		*/

        code,
        kbd,
        samp,
        pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            /* 1 */
            font-size: 1em;
            /* 2 */
        }

        /*
		Add the correct font size in all browsers.
		*/

        small {
            font-size: 80%;
        }

        /*
		Prevent `sub` and `sup` elements from affecting the line height in all browsers.
		*/

        sub,
        sup {
            font-size: 75%;
            line-height: 0;
            position: relative;
            vertical-align: baseline;
        }

        sub {
            bottom: -0.25em;
        }

        sup {
            top: -0.5em;
        }

        /*
		1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
		2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
		3. Remove gaps between table borders by default.
		*/

        table {
            text-indent: 0;
            /* 1 */
            border-color: inherit;
            /* 2 */
            border-collapse: collapse;
            /* 3 */
        }

        /*
		1. Change the font styles in all browsers.
		2. Remove the margin in Firefox and Safari.
		3. Remove default padding in all browsers.
		*/

        button,
        input,
        optgroup,
        select,
        textarea {
            font-family: inherit;
            /* 1 */
            font-feature-settings: inherit;
            /* 1 */
            font-variation-settings: inherit;
            /* 1 */
            font-size: 100%;
            /* 1 */
            font-weight: inherit;
            /* 1 */
            line-height: inherit;
            /* 1 */
            color: inherit;
            /* 1 */
            margin: 0;
            /* 2 */
            padding: 0;
            /* 3 */
        }

        /*
		Remove the inheritance of text transform in Edge and Firefox.
		*/

        button,
        select {
            text-transform: none;
        }

        /*
		1. Correct the inability to style clickable types in iOS and Safari.
		2. Remove default button styles.
		*/

        button,
        [type='button'],
        [type='reset'],
        [type='submit'] {
            -webkit-appearance: button;
            /* 1 */
            background-color: transparent;
            /* 2 */
            background-image: none;
            /* 2 */
        }

        /*
		Use the modern Firefox focus style for all focusable elements.
		*/

        :-moz-focusring {
            outline: auto;
        }

        /*
		Remove the additional `:invalid` styles in Firefox. (https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737)
		*/

        :-moz-ui-invalid {
            box-shadow: none;
        }

        /*
		Add the correct vertical alignment in Chrome and Firefox.
		*/

        progress {
            vertical-align: baseline;
        }

        /*
		Correct the cursor style of increment and decrement buttons in Safari.
		*/

        ::-webkit-inner-spin-button,
        ::-webkit-outer-spin-button {
            height: auto;
        }

        /*
		1. Correct the odd appearance in Chrome and Safari.
		2. Correct the outline style in Safari.
		*/

        [type='search'] {
            -webkit-appearance: textfield;
            /* 1 */
            outline-offset: -2px;
            /* 2 */
        }

        /*
		Remove the inner padding in Chrome and Safari on macOS.
		*/

        ::-webkit-search-decoration {
            -webkit-appearance: none;
        }

        /*
		1. Correct the inability to style clickable types in iOS and Safari.
		2. Change font properties to `inherit` in Safari.
		*/

        ::-webkit-file-upload-button {
            -webkit-appearance: button;
            /* 1 */
            font: inherit;
            /* 2 */
        }

        /*
		Add the correct display in Chrome and Safari.
		*/

        summary {
            display: list-item;
        }

        /*
		Removes the default spacing and border for appropriate elements.
		*/

        blockquote,
        dl,
        dd,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        hr,
        figure,
        p,
        pre {
            margin: 0;
        }

        fieldset {
            margin: 0;
            padding: 0;
        }

        legend {
            padding: 0;
        }

        ol,
        ul,
        menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /*
		Reset default styling for dialogs.
		*/

        dialog {
            padding: 0;
        }

        /*
		Prevent resizing textareas horizontally by default.
		*/

        textarea {
            resize: vertical;
        }

        /*
		1. Reset the default placeholder opacity in Firefox. (https://github.com/tailwindlabs/tailwindcss/issues/3300)
		2. Set the default placeholder color to the user's configured gray 400 color.
		*/

        input::placeholder,
        textarea::placeholder {
            opacity: 1;
            /* 1 */
            color: #9ca3af;
            /* 2 */
        }

        /*
		Set the default cursor for buttons.
		*/

        button,
        [role="button"] {
            cursor: pointer;
        }

        /*
		Make sure disabled buttons don't get the pointer cursor.
		*/

        :disabled {
            cursor: default;
        }

        /*
		1. Make replaced elements `display: block` by default. (https://github.com/mozdevs/cssremedy/issues/14)
		2. Add `vertical-align: middle` to align replaced elements more sensibly by default. (https://github.com/jensimmons/cssremedy/issues/14#issuecomment-634934210)
		   This can trigger a poorly considered lint error in some tools but is included by design.
		*/

        img,
        svg,
        video,
        canvas,
        audio,
        iframe,
        embed,
        object {
            display: block;
            /* 1 */
            vertical-align: middle;
            /* 2 */
        }

        /*
		Constrain images and videos to the parent width and preserve their intrinsic aspect ratio. (https://github.com/mozdevs/cssremedy/issues/14)
		*/

        img,
        video {
            max-width: 100%;
            height: auto;
        }

        /* Make elements with the HTML hidden attribute stay hidden by default */

        [hidden] {
            display: none;
        }

        *, ::before, ::after{
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-pan-x:  ;
            --tw-pan-y:  ;
            --tw-pinch-zoom:  ;
            --tw-scroll-snap-strictness: proximity;
            --tw-gradient-from-position:  ;
            --tw-gradient-via-position:  ;
            --tw-gradient-to-position:  ;
            --tw-ordinal:  ;
            --tw-slashed-zero:  ;
            --tw-numeric-figure:  ;
            --tw-numeric-spacing:  ;
            --tw-numeric-fraction:  ;
            --tw-ring-inset:  ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / 0.5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
            --tw-blur:  ;
            --tw-brightness:  ;
            --tw-contrast:  ;
            --tw-grayscale:  ;
            --tw-hue-rotate:  ;
            --tw-invert:  ;
            --tw-saturate:  ;
            --tw-sepia:  ;
            --tw-drop-shadow:  ;
            --tw-backdrop-blur:  ;
            --tw-backdrop-brightness:  ;
            --tw-backdrop-contrast:  ;
            --tw-backdrop-grayscale:  ;
            --tw-backdrop-hue-rotate:  ;
            --tw-backdrop-invert:  ;
            --tw-backdrop-opacity:  ;
            --tw-backdrop-saturate:  ;
            --tw-backdrop-sepia:
        }

        ::backdrop{
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-pan-x:  ;
            --tw-pan-y:  ;
            --tw-pinch-zoom:  ;
            --tw-scroll-snap-strictness: proximity;
            --tw-gradient-from-position:  ;
            --tw-gradient-via-position:  ;
            --tw-gradient-to-position:  ;
            --tw-ordinal:  ;
            --tw-slashed-zero:  ;
            --tw-numeric-figure:  ;
            --tw-numeric-spacing:  ;
            --tw-numeric-fraction:  ;
            --tw-ring-inset:  ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / 0.5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
            --tw-blur:  ;
            --tw-brightness:  ;
            --tw-contrast:  ;
            --tw-grayscale:  ;
            --tw-hue-rotate:  ;
            --tw-invert:  ;
            --tw-saturate:  ;
            --tw-sepia:  ;
            --tw-drop-shadow:  ;
            --tw-backdrop-blur:  ;
            --tw-backdrop-brightness:  ;
            --tw-backdrop-contrast:  ;
            --tw-backdrop-grayscale:  ;
            --tw-backdrop-hue-rotate:  ;
            --tw-backdrop-invert:  ;
            --tw-backdrop-opacity:  ;
            --tw-backdrop-saturate:  ;
            --tw-backdrop-sepia:
        }

        .relative{
            position: relative
        }

        .mx-auto{
            margin-left: auto;
            margin-right: auto
        }

        .mt-5{
            margin-top: 1.25rem
        }

        .flex{
            display: flex
        }

        .h-screen{
            height: 100vh
        }

        .w-24{
            width: 6rem
        }

        .flex-col{
            flex-direction: column
        }

        .justify-center{
            justify-content: center
        }

        .gap-3{
            gap: 0.75rem
        }

        .rounded-md{
            border-radius: 0.375rem
        }

        .border{
            border-width: 1px
        }

        .border-solid{
            border-style: solid
        }

        .border-green-500{
            --tw-border-opacity: 1;
            border-color: rgb(34 197 94 / var(--tw-border-opacity))
        }

        .bg-gradient-to-r{
            background-image: linear-gradient(to right, var(--tw-gradient-stops))
        }

        .from-green-500{
            --tw-gradient-from: #22c55e var(--tw-gradient-from-position);
            --tw-gradient-to: rgb(34 197 94 / 0) var(--tw-gradient-to-position);
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to)
        }

        .to-blue-600{
            --tw-gradient-to: #2563eb var(--tw-gradient-to-position)
        }

        .bg-clip-text{
            -webkit-background-clip: text;
            background-clip: text
        }

        .p-2{
            padding: 0.5rem
        }

        .p-5{
            padding: 1.25rem
        }

        .text-center{
            text-align: center
        }

        .text-4xl{
            font-size: 2.25rem;
            line-height: 2.5rem
        }

        .text-xl{
            font-size: 1.25rem;
            line-height: 1.75rem
        }

        .font-extrabold{
            font-weight: 800
        }

        .font-extralight{
            font-weight: 200
        }

        .font-thin{
            font-weight: 100
        }

        .text-green-500{
            --tw-text-opacity: 1;
            color: rgb(34 197 94 / var(--tw-text-opacity))
        }

        .text-slate-400{
            --tw-text-opacity: 1;
            color: rgb(148 163 184 / var(--tw-text-opacity))
        }

        .text-slate-700{
            --tw-text-opacity: 1;
            color: rgb(51 65 85 / var(--tw-text-opacity))
        }

        .text-transparent{
            color: transparent
        }

        @media (min-width: 768px){
            .md\:w-40{
                width: 10rem
            }

            .md\:text-2xl{
                font-size: 1.5rem;
                line-height: 2rem
            }

            .md\:text-5xl{
                font-size: 3rem;
                line-height: 1
            }
        }
    </style>
</head>
<body>
    <main class="item-center flex h-screen flex-col gap-3 text-center justify-center p-5">
        <h1 class="relative text-center text-4xl md:text-5xl font-thin text-slate-700">
            <span class="bg-gradient-to-r from-green-500 to-blue-600 bg-clip-text font-extrabold text-transparent"> Page Not Found </span>
        </h1>
        <h3 class="text-center text-xl md:text-2xl font-extralight text-slate-400">The page that you are trying to visit might be broken or not existing.</h3>

        <a href="/" class="mx-auto mt-5 w-24 md:w-40 border border-solid border-green-500 p-2 rounded-md text-green-500">Go Back</a>
    </main>
</body>
</html>
