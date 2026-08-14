<?php

declare(strict_types=1);

// Load functions
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/stats.php";
require_once __DIR__ . "/card.php";

// Load TOKEN from Vercel environment variables
$token = getenv("TOKEN");

if (!$token) {
    renderOutput("Missing TOKEN environment variable.", 500);
}

// Keep compatibility with existing code
$_SERVER["TOKEN"] = $token;


// 🔒 HARD USER LOCK
$ALLOWED_USER = "Balaji-Coder06";

$userParam = $_REQUEST["user"] ?? null;

if ($userParam !== $ALLOWED_USER) {
    header("Cache-Control: no-store");
    header("HTTP/1.1 403 Forbidden");
    echo "Unauthorized";
    exit;
}


// Cache for 3 hours
$cacheMinutes = 3 * 60 * 60;

header("Expires: " . gmdate(
    "D, d M Y H:i:s",
    time() + $cacheMinutes
) . " GMT");

header("Last-Modified: " . gmdate(
    "D, d M Y H:i:s"
) . " GMT");

header("Cache-Control: public, max-age=$cacheMinutes");


try {
    $user = preg_replace(
        "/[^a-zA-Z0-9\-]/",
        "",
        $_REQUEST["user"]
    );

    $startingYear = isset($_REQUEST["starting_year"])
        ? intval($_REQUEST["starting_year"])
        : null;

    $contributionGraphs = getContributionGraphs(
        $user,
        $startingYear
    );

    $contributions = getContributionDates(
        $contributionGraphs
    );

    if (
        isset($_GET["mode"]) &&
        $_GET["mode"] === "weekly"
    ) {
        $stats = getWeeklyContributionStats($contributions);
    } else {
        $excludeDays = normalizeDays(
            explode(",", $_GET["exclude_days"] ?? "")
        );

        $stats = getContributionStats(
            $contributions,
            $excludeDays
        );
    }

    renderOutput($stats);

} catch (InvalidArgumentException | AssertionError $error) {

    error_log(
        "Error {$error->getCode()}: {$error->getMessage()}"
    );

    if ($error->getCode() >= 500) {
        error_log($error->getTraceAsString());
    }

    renderOutput(
        $error->getMessage(),
        $error->getCode()
    );
}