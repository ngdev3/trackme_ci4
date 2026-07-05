<?php /** Sliced content - rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<?php
$me    = current_user();
$name  = ! empty($me['name']) ? $me['name'] : 'Admin';
$hour  = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$k      = isset($kpis) && is_array($kpis) ? $kpis : array();
$act    = (int) (isset($k['active_users']) ? $k['active_users'] : 0);
$inact  = (int) (isset($k['inactive_users']) ? $k['inactive_users'] : 0);
$total  = max(1, $act + $inact);
$lok    = (int) (isset($loginOk) ? $loginOk : 0);
$lfail  = (int) (isset($loginFail) ? $loginFail : 0);
$ltotal = max(1, $lok + $lfail);

$roleLabels = isset($usersByRole['labels']) ? $usersByRole['labels'] : array();
$roleData   = isset($usersByRole['data']) ? $usersByRole['data'] : array();
$userLabels = isset($topUsers['labels']) ? $topUsers['labels'] : array();
$userData   = isset($topUsers['data']) ? $topUsers['data'] : array();
$maxRole    = max(array_merge(array(1), $roleData));
$maxUser    = max(array_merge(array(1), $userData));

$pct = static function ($n, $d) {
    return (int) round(((float) $n / max(1, (int) $d)) * 100);
};

$routine = array(
    array('time' => '09:00', 'title' => 'Start desk review', 'note' => 'Check alerts, approvals, and overnight logins.', 'icon' => 'bi-sunrise'),
    array('time' => '11:30', 'title' => 'User operations', 'note' => 'Create accounts, assign roles, and clear access requests.', 'icon' => 'bi-person-check'),
    array('time' => '14:00', 'title' => 'Module audit', 'note' => 'Review permissions, new activity, and role coverage.', 'icon' => 'bi-grid-3x3-gap'),
    array('time' => '17:30', 'title' => 'Closeout snapshot', 'note' => 'Confirm login health and export anything pending.', 'icon' => 'bi-clipboard2-check'),
);
?>

<section class="dash-landing dash-reveal">
    <div class="dash-landing-copy">
        <span class="dash-eyebrow"><i class="bi bi-stars"></i> Live ERP command center</span>
        <h2><?= esc($greet) ?>, <?= esc($name) ?></h2>
        <p><?= esc(date('l, d M Y')) ?>. Monitor users, security, weather, and daily work rhythm from one smooth dashboard.</p>
        <div class="dash-landing-actions">
            <a href="<?= site_url('users/create') ?>" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>New User</a>
            <a href="<?= site_url('roles') ?>" class="btn btn-outline-light"><i class="bi bi-shield-lock me-1"></i>Roles</a>
            <button class="btn btn-outline-light" id="btnRefresh" type="button"><i class="bi bi-arrow-repeat me-1"></i>Refresh</button>
        </div>
    </div>
    <div class="dash-landing-visual" aria-label="Dashboard signal overview">
        <div class="signal-card signal-card-main">
            <span>Login Health</span>
            <strong><?= $pct($lok, $ltotal) ?>%</strong>
            <small>successful this week</small>
        </div>
        <div class="signal-bars">
            <span style="height:42%"></span>
            <span style="height:68%"></span>
            <span style="height:52%"></span>
            <span style="height:82%"></span>
            <span style="height:60%"></span>
        </div>
    </div>
</section>

<section class="dash-weather-band">
    <article class="dash-card dash-weather dash-reveal weather-loading" id="weatherCard">
        <div class="weather-sky" aria-hidden="true">
            <span class="weather-sun"></span>
            <span class="weather-cloud cloud-one"></span>
            <span class="weather-cloud cloud-two"></span>
            <span class="weather-rain"></span>
        </div>

        <div class="weather-strip">
            <div class="weather-now">
                <span class="weather-orb"><i class="bi bi-cloud-sun"></i></span>
                <div class="weather-now-copy">
                    <div class="weather-temp"><span id="weatherTemp">--</span><sup>deg C</sup></div>
                    <strong id="weatherSummary">Checking conditions</strong>
                    <div class="weather-meta">
                        <span><i class="bi bi-geo-alt-fill"></i><b id="weatherPlace">Loading location</b></span>
                        <span><i class="bi bi-moisture"></i><b id="weatherHumidity">--</b> humidity</span>
                        <span><i class="bi bi-cloud-rain"></i><b id="weatherRainChance">--</b> rain</span>
                        <span><i class="bi bi-wind"></i><b id="weatherWind">--</b> wind</span>
                    </div>
                </div>
            </div>

            <div class="weather-week" id="weatherWeek" aria-label="One week forecast">
                <span>Loading week forecast</span>
            </div>

            <div class="weather-controls">
                <button class="weather-link" type="button" id="weatherChangeBtn"><i class="bi bi-pencil"></i> Change city</button>
                <button class="weather-link" type="button" id="weatherLocateBtn"><i class="bi bi-crosshair"></i> Get location</button>
                <form class="weather-city-form" id="weatherCityForm" autocomplete="off">
                    <input type="search" id="weatherCityInput" placeholder="Enter city name" aria-label="Enter city name">
                    <button type="submit">Show</button>
                </form>
                <small id="weatherThemeLabel">Theme: Live</small>
            </div>
        </div>
    </article>
</section>

<section class="dash-kpi-grid">
    <article class="dash-card dash-kpi dash-reveal">
        <span class="dash-card-icon blue"><i class="bi bi-people"></i></span>
        <div>
            <p>Total Accounts</p>
            <strong data-count="<?= (int) (isset($k['total_users']) ? $k['total_users'] : 0) ?>">0</strong>
        </div>
        <small><?= $pct($act, $total) ?>% active</small>
    </article>
    <article class="dash-card dash-kpi dash-reveal">
        <span class="dash-card-icon green"><i class="bi bi-person-check"></i></span>
        <div>
            <p>Active Users</p>
            <strong data-count="<?= $act ?>">0</strong>
        </div>
        <small><?= $inact ?> inactive</small>
    </article>
    <article class="dash-card dash-kpi dash-reveal">
        <span class="dash-card-icon violet"><i class="bi bi-person-gear"></i></span>
        <div>
            <p>Total Roles</p>
            <strong data-count="<?= (int) (isset($k['total_roles']) ? $k['total_roles'] : 0) ?>">0</strong>
        </div>
        <small>Access structure</small>
    </article>
    <article class="dash-card dash-kpi dash-reveal">
        <span class="dash-card-icon amber"><i class="bi bi-grid-3x3-gap"></i></span>
        <div>
            <p>Modules</p>
            <strong data-count="<?= (int) (isset($k['total_modules']) ? $k['total_modules'] : 0) ?>">0</strong>
        </div>
        <small><?= (int) (isset($k['activity_today']) ? $k['activity_today'] : 0) ?> actions today</small>
    </article>
</section>

<section class="dash-routine-grid">
    <article class="dash-card dash-routine dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Daily Routine</span>
                <h4>Today showcase</h4>
            </div>
            <span id="lastUpdated" class="dash-time-chip">Syncing</span>
        </div>
        <div class="routine-list">
            <?php foreach ($routine as $item): ?>
                <?php $isNow = ((int) substr($item['time'], 0, 2)) <= $hour && $hour < ((int) substr($item['time'], 0, 2) + 3); ?>
                <div class="routine-item<?= $isNow ? ' is-now' : '' ?>">
                    <span class="routine-time"><?= esc($item['time']) ?></span>
                    <span class="routine-icon"><i class="bi <?= esc($item['icon']) ?>"></i></span>
                    <span class="routine-copy">
                        <strong><?= esc($item['title']) ?></strong>
                        <small><?= esc($item['note']) ?></small>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="dash-chart-grid">
    <article class="dash-card dash-chart-card dash-reveal wide">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Line Graph</span>
                <h4>Login Activity</h4>
            </div>
            <span class="dash-chip">1 week</span>
        </div>
        <div class="chart-box"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartLogins"></canvas></div>
    </article>

    <article class="dash-card dash-chart-card dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Doughnut</span>
                <h4>Users by Type</h4>
            </div>
            <span class="dash-chip">Live</span>
        </div>
        <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartUsersType"></canvas></div>
    </article>

    <article class="dash-card dash-chart-card dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Pie Chart</span>
                <h4>Users by Role</h4>
            </div>
            <span class="dash-chip"><?= count($roleLabels) ?> roles</span>
        </div>
        <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartUsersRole"></canvas></div>
    </article>

    <article class="dash-card dash-chart-card dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Bar Chart</span>
                <h4>New Users</h4>
            </div>
            <span class="dash-chip">6 months</span>
        </div>
        <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartGrowth"></canvas></div>
    </article>

    <article class="dash-card dash-chart-card dash-reveal wide">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Horizontal Bars</span>
                <h4>Activity by Action</h4>
            </div>
            <span class="dash-chip">30 days</span>
        </div>
        <div class="chart-box-sm"><div class="chart-skeleton skeleton skeleton-chart"></div><canvas id="chartActivity"></canvas></div>
    </article>
</section>

<section class="dash-main-grid">
    <article class="dash-card dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Top 5</span>
                <h4>Active Users</h4>
            </div>
            <i class="bi bi-trophy text-primary"></i>
        </div>
        <?php if (empty($userLabels)): ?>
            <p class="text-secondary mb-0">No activity in the last 30 days.</p>
        <?php else: foreach ($userLabels as $i => $userName): ?>
            <div class="dash-strip">
                <span class="dash-rank"><?= $i + 1 ?></span>
                <span class="dash-strip-body">
                    <strong><?= esc($userName) ?></strong>
                    <span><i style="width:<?= $pct($userData[$i], $maxUser) ?>%"></i></span>
                </span>
                <b><?= (int) $userData[$i] ?></b>
            </div>
        <?php endforeach; endif; ?>
    </article>

    <article class="dash-card dash-reveal">
        <div class="dash-card-head">
            <div>
                <span class="dash-section-kicker">Distribution</span>
                <h4>Users by Role</h4>
            </div>
            <i class="bi bi-pie-chart text-primary"></i>
        </div>
        <?php if (empty($roleLabels)): ?>
            <p class="text-secondary mb-0">No roles found.</p>
        <?php else: foreach (array_slice($roleLabels, 0, 5) as $i => $roleName): ?>
            <div class="dash-strip">
                <span class="dash-rank"><?= $i + 1 ?></span>
                <span class="dash-strip-body">
                    <strong><?= esc($roleName) ?></strong>
                    <span><i style="width:<?= $pct($roleData[$i], $maxRole) ?>%"></i></span>
                </span>
                <b><?= (int) $roleData[$i] ?></b>
            </div>
        <?php endforeach; endif; ?>
    </article>
</section>
