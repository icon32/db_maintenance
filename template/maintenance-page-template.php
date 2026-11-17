<?php
/**
 * Front-end maintenance notice.
 *
 * Included directly on `init` and then exits, so it has to be a complete,
 * self-contained document: no wp_head(), no theme, no external requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$dbcern_options = function_exists( 'dbcern_maintenance_get_options' )
    ? dbcern_maintenance_get_options()
    : array();

$dbcern_logo_id    = isset( $dbcern_options['page_image'] ) ? absint( $dbcern_options['page_image'] ) : 0;
$dbcern_logo_white = ! empty( $dbcern_options['page_image_white'] );
$dbcern_heading = isset( $dbcern_options['page_title'] ) ? trim( (string) $dbcern_options['page_title'] ) : '';
$dbcern_message = isset( $dbcern_options['page_content'] ) ? trim( (string) $dbcern_options['page_content'] ) : '';

if ( '' === $dbcern_heading ) {
    $dbcern_heading = 'We will be back shortly';
}

if ( '' === $dbcern_message ) {
    $dbcern_message = 'This site is briefly offline while we make a few updates. Thanks for your patience.';
}

$dbcern_site_name = get_bloginfo( 'name', 'display' );
$dbcern_site_host = wp_parse_url( home_url(), PHP_URL_HOST );
$dbcern_language  = get_bloginfo( 'language' );

$dbcern_logo_html = '';
if ( $dbcern_logo_id && wp_attachment_is_image( $dbcern_logo_id ) ) {
    $dbcern_logo_html = wp_get_attachment_image(
        $dbcern_logo_id,
        'medium',
        false,
        array(
            'class' => $dbcern_logo_white ? 'mark__image mark__image--white' : 'mark__image',
            'alt'   => $dbcern_site_name,
        )
    );
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $dbcern_language ); ?>">
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#06141A">
<title><?php echo esc_html( '' !== $dbcern_site_name ? $dbcern_site_name . ': back shortly' : 'Back shortly' ); ?></title>
<style>
    :root {
        --void: #06141A;
        --jade: #0F7A6B;
        --aqua: #1BA8A0;
        --deep: #15356B;
        --gold: #D6A861;
        --paper: #F2F8F6;
        --muted: #B6CECA;

        --serif: "Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
        --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

        --edge: clamp(1.25rem, 4vw, 3rem);
    }

    * { box-sizing: border-box; }

    html {
        font-size: 17px;
        -webkit-text-size-adjust: 100%;
    }

    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background-color: var(--void);
        color: var(--paper);
        font-family: var(--sans);
        line-height: 1.6;
        color-scheme: dark;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    @supports (min-height: 100dvh) {
        body { min-height: 100dvh; }
    }

    /* ---- the living background ---------------------------------------- */

    .aurora {
        position: fixed;
        inset: -25%;
        z-index: 0;
        pointer-events: none;
        filter: blur(90px);
    }

    .aurora span {
        position: absolute;
        display: block;
        border-radius: 50%;
        mix-blend-mode: screen;
        will-change: transform;
    }

    .aurora__a {
        width: 60vmax; height: 60vmax;
        top: -8%; left: -6%;
        background: radial-gradient(circle at 50% 50%, var(--jade) 0%, rgba(15,122,107,0.55) 38%, rgba(15,122,107,0) 72%);
        animation: drift-a 34s ease-in-out infinite alternate;
    }

    .aurora__b {
        width: 52vmax; height: 52vmax;
        bottom: -12%; right: -8%;
        background: radial-gradient(circle at 50% 50%, var(--aqua) 0%, rgba(27,168,160,0.5) 36%, rgba(27,168,160,0) 70%);
        opacity: 0.95;
        animation: drift-b 46s ease-in-out infinite alternate;
    }

    .aurora__c {
        width: 46vmax; height: 46vmax;
        top: 28%; left: 42%;
        background: radial-gradient(circle at 50% 50%, var(--deep) 0%, rgba(21,53,107,0.55) 40%, rgba(21,53,107,0) 74%);
        opacity: 0.9;
        animation: drift-c 58s ease-in-out infinite alternate;
    }

    @keyframes drift-a {
        from { transform: translate3d(0, 0, 0) scale(1); }
        to   { transform: translate3d(14vw, 10vh, 0) scale(1.18); }
    }
    @keyframes drift-b {
        from { transform: translate3d(0, 0, 0) scale(1.1); }
        to   { transform: translate3d(-16vw, -12vh, 0) scale(0.92); }
    }
    @keyframes drift-c {
        from { transform: translate3d(0, 0, 0) scale(0.95); }
        to   { transform: translate3d(-12vw, 14vh, 0) scale(1.22); }
    }

    /* Darkens the edges so the panel sits in the light, not the glare. */
    .vignette {
        position: fixed;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background: radial-gradient(ellipse at 50% 45%, rgba(6,20,26,0) 42%, rgba(6,20,26,0.55) 100%);
    }

    /* Fine grain: stops the big gradients banding on 8-bit screens. */
    .grain {
        position: fixed;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        opacity: 0.04;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* ---- content ------------------------------------------------------- */

    .stage {
        position: relative;
        z-index: 3;
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(2rem, 6vh, 4rem) var(--edge);
    }

    .panel {
        width: 100%;
        max-width: 40rem;
        padding: clamp(2.25rem, 6vw, 3.75rem);
        text-align: center;
        border-radius: 22px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        /* Smoked, not clear: a light panel over the aurora washes the text out. */
        background:
            linear-gradient(180deg, rgba(13, 38, 44, 0.78) 0%, rgba(6, 20, 26, 0.86) 100%);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.18) inset,
            0 40px 90px -25px rgba(0, 0, 0, 0.75);
    }

    @supports (backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px)) {
        .panel {
            -webkit-backdrop-filter: blur(22px) saturate(150%);
            backdrop-filter: blur(22px) saturate(150%);
        }
    }

    .mark {
        margin-bottom: clamp(1.5rem, 4vh, 2.25rem);
    }

    .mark__image {
        display: block;
        margin: 0 auto;
        width: auto;
        max-width: min(12rem, 65%);
        height: auto;
    }

    /* Forces a single-colour logo to pure white so it reads on the dark page. */
    .mark__image--white {
        filter: brightness(0) invert(1);
    }

    .mark__name {
        margin: 0;
        font-family: var(--serif);
        font-size: 1.0625rem;
        letter-spacing: 0.06em;
        color: var(--muted);
    }

    .panel__heading {
        margin: 0;
        font-family: var(--serif);
        font-weight: 400;
        font-size: clamp(2.125rem, 5.5vw, 3.5rem);
        line-height: 1.08;
        letter-spacing: -0.015em;
        text-wrap: balance;
        background: linear-gradient(180deg, #FFFFFF 0%, var(--paper) 60%, #D2E4E0 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .panel__glow {
        width: 7rem;
        height: 1px;
        margin: clamp(1.5rem, 3.5vh, 2rem) auto;
        background: linear-gradient(90deg, rgba(214,168,97,0) 0%, var(--gold) 50%, rgba(214,168,97,0) 100%);
    }

    .panel__message {
        margin: 0 auto;
        max-width: 28rem;
        font-size: 1.0625rem;
        line-height: 1.7;
        color: var(--muted);
    }

    .panel__message p { margin: 0 0 0.9em; }
    .panel__message p:last-child { margin-bottom: 0; }

    .panel__message a {
        color: var(--gold);
        text-decoration-thickness: 1px;
        text-underline-offset: 0.2em;
    }

    .footer {
        position: relative;
        z-index: 3;
        flex: 0 0 auto;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1.5rem;
        align-items: baseline;
        justify-content: space-between;
        padding: 0 var(--edge) clamp(1.5rem, 4vh, 2.5rem);
        font-size: 0.875rem;
        color: rgba(159, 181, 180, 0.75);
    }

    .footer__login {
        color: rgba(159, 181, 180, 0.75);
        text-decoration: none;
        border-bottom: 1px solid rgba(159, 181, 180, 0.3);
        padding-bottom: 0.15em;
        transition: color 180ms ease, border-color 180ms ease;
    }

    .footer__login:hover,
    .footer__login:focus-visible {
        color: var(--gold);
        border-color: var(--gold);
    }

    :focus-visible {
        outline: 2px solid var(--gold);
        outline-offset: 4px;
        border-radius: 2px;
    }

    /* ---- entrance ------------------------------------------------------ */

    @keyframes settle {
        from { opacity: 0; transform: translateY(1rem) scale(0.985); }
        to   { opacity: 1; transform: none; }
    }

    @keyframes fade-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    .panel  { animation: settle 900ms cubic-bezier(0.2, 0.7, 0.2, 1) both; }
    .footer { animation: fade-in 900ms 450ms ease both; }

    @media (prefers-reduced-motion: reduce) {
        .aurora span,
        .panel,
        .footer {
            animation: none !important;
            opacity: 1;
            transform: none;
        }
        .aurora__b { opacity: 0.85; }
        .aurora__c { opacity: 0.9; }
    }

    @media (max-width: 30rem) {
        html { font-size: 16px; }
        .panel { border-radius: 18px; }
        .aurora { filter: blur(60px); }
    }
</style>
</head>
<body>

<div class="aurora" aria-hidden="true">
    <span class="aurora__a"></span>
    <span class="aurora__b"></span>
    <span class="aurora__c"></span>
</div>
<div class="vignette" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

<main class="stage">
    <section class="panel">

        <div class="mark">
            <?php if ( '' !== $dbcern_logo_html ) : ?>
                <?php echo $dbcern_logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image(). ?>
            <?php elseif ( '' !== $dbcern_site_name ) : ?>
                <p class="mark__name"><?php echo esc_html( $dbcern_site_name ); ?></p>
            <?php endif; ?>
        </div>

        <h1 class="panel__heading"><?php echo esc_html( $dbcern_heading ); ?></h1>

        <div class="panel__glow" aria-hidden="true"></div>

        <div class="panel__message">
            <?php echo wpautop( wp_kses_post( $dbcern_message ) ); ?>
        </div>

    </section>
</main>

<footer class="footer">
    <span class="footer__site"><?php echo esc_html( $dbcern_site_host ); ?></span>
    <a class="footer__login" href="<?php echo esc_url( wp_login_url() ); ?>">Log in</a>
</footer>

</body>
</html>
