// ---------------------------------------------------------------------------
// 2K25 Ratings Lab — dados e lógica do site (vanilla JS)
// Ratings oficiais de lançamento de NBA 2K25 (fonte: NBA.com / 2K).
// Time e posição refletem os elencos no lançamento do jogo (temporada 2024-25).
// ---------------------------------------------------------------------------

const players = [
  { rank: 1, name: "Nikola Jokić", team: "Denver Nuggets", position: "C", ovr: 97 },
  { rank: 2, name: "Luka Dončić", team: "Dallas Mavericks", position: "PG", ovr: 97 },
  { rank: 3, name: "Giannis Antetokounmpo", team: "Milwaukee Bucks", position: "PF", ovr: 97 },
  { rank: 4, name: "Shai Gilgeous-Alexander", team: "Oklahoma City Thunder", position: "PG", ovr: 96 },
  { rank: 5, name: "Joel Embiid", team: "Philadelphia 76ers", position: "C", ovr: 96 },
  { rank: 6, name: "LeBron James", team: "Los Angeles Lakers", position: "SF", ovr: 95 },
  { rank: 7, name: "Stephen Curry", team: "Golden State Warriors", position: "PG", ovr: 95 },
  { rank: 8, name: "Jayson Tatum", team: "Boston Celtics", position: "SF", ovr: 95 },
  { rank: 9, name: "Kevin Durant", team: "Phoenix Suns", position: "PF", ovr: 94 },
  { rank: 10, name: "Anthony Davis", team: "Los Angeles Lakers", position: "PF", ovr: 94 },
  { rank: 11, name: "Anthony Edwards", team: "Minnesota Timberwolves", position: "SG", ovr: 93 },
  { rank: 12, name: "Devin Booker", team: "Phoenix Suns", position: "SG", ovr: 93 },
  { rank: 13, name: "Jalen Brunson", team: "New York Knicks", position: "PG", ovr: 93 },
  { rank: 14, name: "Donovan Mitchell", team: "Cleveland Cavaliers", position: "SG", ovr: 92 },
  { rank: 15, name: "Jaylen Brown", team: "Boston Celtics", position: "SG", ovr: 92 },
  { rank: 16, name: "Kyrie Irving", team: "Dallas Mavericks", position: "PG", ovr: 92 },
  { rank: 17, name: "Kawhi Leonard", team: "Los Angeles Clippers", position: "SF", ovr: 92 },
  { rank: 18, name: "Victor Wembanyama", team: "San Antonio Spurs", position: "C", ovr: 91 },
  { rank: 19, name: "Tyrese Haliburton", team: "Indiana Pacers", position: "PG", ovr: 90 },
  { rank: 20, name: "Ja Morant", team: "Memphis Grizzlies", position: "PG", ovr: 90 },
  { rank: 21, name: "Damian Lillard", team: "Milwaukee Bucks", position: "PG", ovr: 89 },
  { rank: 22, name: "Jimmy Butler", team: "Miami Heat", position: "SF", ovr: 89 },
  { rank: 23, name: "Paolo Banchero", team: "Orlando Magic", position: "PF", ovr: 89 },
  { rank: 24, name: "Paul George", team: "Philadelphia 76ers", position: "SF", ovr: 89 },
  { rank: 25, name: "Trae Young", team: "Atlanta Hawks", position: "PG", ovr: 89 },
  { rank: 26, name: "Tyrese Maxey", team: "Philadelphia 76ers", position: "PG", ovr: 89 },
  { rank: 27, name: "Bam Adebayo", team: "Miami Heat", position: "C", ovr: 88 },
  { rank: 28, name: "De'Aaron Fox", team: "Sacramento Kings", position: "PG", ovr: 88 },
  { rank: 29, name: "Domantas Sabonis", team: "Sacramento Kings", position: "C", ovr: 88 },
  { rank: 30, name: "Zion Williamson", team: "New Orleans Pelicans", position: "PF", ovr: 88 },
  { rank: 31, name: "Pascal Siakam", team: "Indiana Pacers", position: "PF", ovr: 88 },
  { rank: 32, name: "Karl-Anthony Towns", team: "New York Knicks", position: "C", ovr: 88 },
  { rank: 33, name: "LaMelo Ball", team: "Charlotte Hornets", position: "PG", ovr: 87 },
  { rank: 34, name: "Jrue Holiday", team: "Boston Celtics", position: "PG", ovr: 87 },
  { rank: 35, name: "DeMar DeRozan", team: "Sacramento Kings", position: "SF", ovr: 87 },
  { rank: 36, name: "Chet Holmgren", team: "Oklahoma City Thunder", position: "C", ovr: 87 },
  { rank: 37, name: "Kristaps Porziņģis", team: "Boston Celtics", position: "C", ovr: 87 },
  { rank: 38, name: "Jamal Murray", team: "Denver Nuggets", position: "PG", ovr: 87 },
  { rank: 39, name: "Jaren Jackson Jr.", team: "Memphis Grizzlies", position: "PF", ovr: 87 },
  { rank: 40, name: "Lauri Markkanen", team: "Utah Jazz", position: "PF", ovr: 86 },
  { rank: 41, name: "Cade Cunningham", team: "Detroit Pistons", position: "PG", ovr: 86 },
  { rank: 42, name: "Jalen Williams", team: "Oklahoma City Thunder", position: "SF", ovr: 86 },
  { rank: 43, name: "Franz Wagner", team: "Orlando Magic", position: "SF", ovr: 86 },
  { rank: 44, name: "Derrick White", team: "Boston Celtics", position: "PG", ovr: 86 },
  { rank: 45, name: "Dejounte Murray", team: "New Orleans Pelicans", position: "PG", ovr: 86 },
  { rank: 46, name: "Evan Mobley", team: "Cleveland Cavaliers", position: "PF", ovr: 86 },
  { rank: 47, name: "Scottie Barnes", team: "Toronto Raptors", position: "PF", ovr: 85 },
  { rank: 48, name: "Julius Randle", team: "Minnesota Timberwolves", position: "PF", ovr: 85 },
  { rank: 49, name: "Brandon Ingram", team: "New Orleans Pelicans", position: "SF", ovr: 85 },
  { rank: 50, name: "Alperen Şengün", team: "Houston Rockets", position: "C", ovr: 85 },
  { rank: 51, name: "Rudy Gobert", team: "Minnesota Timberwolves", position: "C", ovr: 85 },
  { rank: 52, name: "Bradley Beal", team: "Phoenix Suns", position: "SG", ovr: 85 },
  { rank: 53, name: "Khris Middleton", team: "Milwaukee Bucks", position: "SF", ovr: 85 },
  { rank: 54, name: "Mikal Bridges", team: "New York Knicks", position: "SF", ovr: 84 },
  { rank: 55, name: "OG Anunoby", team: "New York Knicks", position: "SF", ovr: 84 },
  { rank: 56, name: "Jalen Green", team: "Houston Rockets", position: "SG", ovr: 84 },
  { rank: 57, name: "Fred VanVleet", team: "Houston Rockets", position: "PG", ovr: 84 },
  { rank: 58, name: "James Harden", team: "Los Angeles Clippers", position: "PG", ovr: 84 },
  { rank: 59, name: "Jarrett Allen", team: "Cleveland Cavaliers", position: "C", ovr: 84 },
  { rank: 60, name: "CJ McCollum", team: "New Orleans Pelicans", position: "SG", ovr: 84 },
]

const TIERS = [
  { min: 95, label: "Elite", accent: "#f0d878" },
  { min: 90, label: "Superstar", accent: "#d4af37" },
  { min: 85, label: "All-Star", accent: "#b8933f" },
  { min: 0, label: "Titular", accent: "#8a7a52" },
]

const POSITION_LABELS = { PG: "Armador", SG: "Ala-Armador", SF: "Ala", PF: "Ala-Pivô", C: "Pivô" }
const POSITIONS = ["PG", "SG", "SF", "PF", "C"]
const GOLD_SHADES = ["#f0d878", "#d4af37", "#b8933f", "#9c7a1f", "#7a6119"]

function getTier(ovr) {
  return TIERS.find((t) => ovr >= t.min) ?? TIERS[TIERS.length - 1]
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c])
}

// ---------------------------------------------------------------------------
// Estado
// ---------------------------------------------------------------------------

const state = {
  query: "",
  positionFilter: "ALL",
  sortKey: "rank",
  sortAsc: true,
}

const averageOvr = Math.round((players.reduce((sum, p) => sum + p.ovr, 0) / players.length) * 10) / 10
const topPlayer = players[0]

// ---------------------------------------------------------------------------
// Hero
// ---------------------------------------------------------------------------

function renderHero() {
  const card = document.getElementById("hero-card")
  card.innerHTML = `
    <p class="hc-label">Maior OVR da liga</p>
    <p class="hc-ovr text-gold-foil">${topPlayer.ovr}</p>
    <p class="hc-name">${escapeHtml(topPlayer.name)}</p>
    <p class="hc-team">${escapeHtml(topPlayer.team)} · ${topPlayer.position}</p>
    <div class="hc-stats">
      <div>
        <p class="hc-num">${averageOvr}</p>
        <p class="hc-sub">OVR médio</p>
      </div>
      <div>
        <p class="hc-num">${players.length}</p>
        <p class="hc-sub">jogadores</p>
      </div>
    </div>
  `

  document.getElementById("hero-desc").textContent =
    `Explore o overall, a posição e o time de ${players.length} dos jogadores mais bem avaliados no lançamento de NBA 2K25. Compare tiers, filtre por posição e descubra quem carrega o elenco mais forte da liga.`
}

// ---------------------------------------------------------------------------
// Marquee (ticker)
// ---------------------------------------------------------------------------

function renderMarquee() {
  const track = document.getElementById("marquee-track")
  const topTen = players.slice(0, 10)
  const items = [...topTen, ...topTen]

  track.innerHTML = items
    .map(
      (p) => `
      <div class="marquee-item">
        <span class="m-ovr">${p.ovr}</span>
        <span class="m-name">${escapeHtml(p.name)}</span>
        <span class="m-team">${escapeHtml(p.team)}</span>
        <span class="m-sep">/</span>
      </div>
    `,
    )
    .join("")
}

// ---------------------------------------------------------------------------
// Estatísticas (KPIs + gráficos em CSS puro)
// ---------------------------------------------------------------------------

function renderStats() {
  const teamAverages = Object.entries(
    players.reduce((acc, p) => {
      acc[p.team] = acc[p.team] || []
      acc[p.team].push(p.ovr)
      return acc
    }, {}),
  )
    .map(([team, ovrs]) => ({
      team,
      average: Math.round((ovrs.reduce((a, b) => a + b, 0) / ovrs.length) * 10) / 10,
    }))
    .sort((a, b) => b.average - a.average)
    .slice(0, 8)

  const bestTeam = teamAverages[0]

  // KPIs
  const kpis = [
    { label: "Jogadores analisados", value: players.length.toString() },
    { label: "OVR médio do elenco", value: averageOvr.toFixed(1) },
    { label: "Melhor OVR", value: Math.max(...players.map((p) => p.ovr)).toString() },
    { label: "Time mais forte (média)", value: bestTeam ? bestTeam.team : "—" },
  ]
  document.getElementById("kpi-grid").innerHTML = kpis
    .map(
      (k) => `
      <div class="card-edge kpi-card">
        <p class="kpi-value" title="${escapeHtml(k.value)}">${escapeHtml(k.value)}</p>
        <p class="kpi-label">${escapeHtml(k.label)}</p>
      </div>
    `,
    )
    .join("")

  // Distribuição por posição (barras verticais)
  const positionCounts = POSITIONS.map((pos) => ({
    label: POSITION_LABELS[pos],
    count: players.filter((p) => p.position === pos).length,
  }))
  const maxCount = Math.max(...positionCounts.map((p) => p.count))

  document.getElementById("position-chart").innerHTML = positionCounts
    .map((p, i) => {
      const heightPct = Math.max(6, Math.round((p.count / maxCount) * 100))
      return `
        <div class="vbar">
          <span class="vbar-count">${p.count}</span>
          <div class="vbar-fill" style="height:${heightPct}%; background:${GOLD_SHADES[i % GOLD_SHADES.length]}"></div>
          <span class="vbar-label">${escapeHtml(p.label)}</span>
        </div>
      `
    })
    .join("")

  // Times com elenco mais forte (barras horizontais)
  document.getElementById("team-chart").innerHTML = teamAverages
    .map((t) => {
      const widthPct = Math.round(((t.average - 75) / 25) * 100)
      return `
        <div class="hbar-row">
          <span class="hbar-team" title="${escapeHtml(t.team)}">${escapeHtml(t.team)}</span>
          <div class="hbar-track"><div class="hbar-fill" style="width:${Math.max(4, widthPct)}%"></div></div>
          <span class="hbar-value">${t.average}</span>
        </div>
      `
    })
    .join("")
}

// ---------------------------------------------------------------------------
// Filtros de posição
// ---------------------------------------------------------------------------

function renderPositionFilters() {
  const container = document.getElementById("position-filters")
  const options = [{ key: "ALL", label: "Todas" }, ...POSITIONS.map((p) => ({ key: p, label: p }))]

  container.innerHTML = options
    .map(
      (opt) => `
      <button class="filter-btn${state.positionFilter === opt.key ? " active" : ""}" data-pos="${opt.key}">
        ${escapeHtml(opt.label)}
      </button>
    `,
    )
    .join("")

  container.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.positionFilter = btn.dataset.pos
      renderPositionFilters()
      renderTable()
    })
  })
}

// ---------------------------------------------------------------------------
// Tabela de jogadores
// ---------------------------------------------------------------------------

function getFilteredSortedPlayers() {
  const q = state.query.trim().toLowerCase()

  let list = players.filter((p) => {
    const matchesQuery = q.length === 0 || p.name.toLowerCase().includes(q) || p.team.toLowerCase().includes(q)
    const matchesPosition = state.positionFilter === "ALL" || p.position === state.positionFilter
    return matchesQuery && matchesPosition
  })

  list = [...list].sort((a, b) => {
    let result = 0
    if (state.sortKey === "name") result = a.name.localeCompare(b.name)
    else if (state.sortKey === "team") result = a.team.localeCompare(b.team)
    else if (state.sortKey === "ovr") result = a.ovr - b.ovr
    else result = a.rank - b.rank
    return state.sortAsc ? result : -result
  })

  return list
}

function renderTableHeaderSortState() {
  document.querySelectorAll("th[data-sort]").forEach((th) => {
    const key = th.dataset.sort
    const existingArrow = th.querySelector(".sort-arrow")
    if (existingArrow) existingArrow.remove()
    if (key === state.sortKey) {
      const arrow = document.createElement("span")
      arrow.className = "sort-arrow"
      arrow.textContent = state.sortAsc ? "↑" : "↓"
      th.appendChild(arrow)
    }
  })
}

function renderTable() {
  const tbody = document.getElementById("players-tbody")
  const list = getFilteredSortedPlayers()

  if (list.length === 0) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Nenhum jogador encontrado para essa busca.</td></tr>`
    renderTableHeaderSortState()
    return
  }

  tbody.innerHTML = list
    .map((p) => {
      const tier = getTier(p.ovr)
      return `
        <tr data-rank="${p.rank}" tabindex="0">
          <td class="rank-cell">${p.rank}</td>
          <td class="name-cell">${escapeHtml(p.name)}</td>
          <td class="team-cell">${escapeHtml(p.team)}</td>
          <td class="ovr-cell">
            <span class="ovr-badge" style="background:${tier.accent}22; color:${tier.accent}">${p.ovr}</span>
          </td>
          <td class="pos-cell">${p.position}</td>
        </tr>
      `
    })
    .join("")

  tbody.querySelectorAll("tr[data-rank]").forEach((row) => {
    const openDetail = () => {
      const player = players.find((p) => p.rank === Number(row.dataset.rank))
      if (player) openDetailPanel(player)
    }
    row.addEventListener("click", openDetail)
    row.addEventListener("keydown", (e) => {
      if (e.key === "Enter") openDetail()
    })
  })

  renderTableHeaderSortState()
}

function setupTableSorting() {
  document.querySelectorAll("th[data-sort]").forEach((th) => {
    th.addEventListener("click", () => {
      const key = th.dataset.sort
      if (state.sortKey === key) {
        state.sortAsc = !state.sortAsc
      } else {
        state.sortKey = key
        state.sortAsc = key !== "ovr"
      }
      renderTable()
    })
  })
}

// ---------------------------------------------------------------------------
// Painel de detalhes
// ---------------------------------------------------------------------------

function openDetailPanel(player) {
  const tier = getTier(player.ovr)
  const diff = Math.round((player.ovr - averageOvr) * 10) / 10
  const barWidth = Math.min(100, Math.round(((player.ovr - 70) / 30) * 100))
  const diffColor = diff >= 0 ? "#f0d878" : "#e08787"

  document.getElementById("detail-content").innerHTML = `
    <p class="detail-tier" style="color:${tier.accent}">${tier.label} · #${player.rank} geral</p>
    <h2 class="detail-name">${escapeHtml(player.name)}</h2>
    <p class="detail-team">${escapeHtml(player.team)} · ${player.position}</p>

    <div class="detail-ovr-row">
      <span class="detail-ovr text-gold-foil">${player.ovr}</span>
      <span class="detail-ovr-label">Overall Rating</span>
    </div>

    <div class="detail-scale"><span>70</span><span>100</span></div>
    <div class="detail-track">
      <div class="detail-fill" style="width:${barWidth}%; background:linear-gradient(90deg, #9c7a1f, ${tier.accent})"></div>
    </div>

    <div class="detail-grid">
      <div class="card-edge">
        <p class="dg-value">${averageOvr.toFixed(1)}</p>
        <p class="dg-label">OVR médio da liga</p>
      </div>
      <div class="card-edge">
        <p class="dg-value" style="color:${diffColor}">${diff >= 0 ? "+" : ""}${diff}</p>
        <p class="dg-label">Vs. média da liga</p>
      </div>
    </div>

    <p class="detail-note">Rating oficial de lançamento de NBA 2K25, com base no desempenho na temporada 2024-25.</p>
  `

  document.getElementById("detail-overlay").classList.add("open")
}

function closeDetailPanel() {
  document.getElementById("detail-overlay").classList.remove("open")
}

function setupDetailPanel() {
  document.getElementById("overlay-backdrop").addEventListener("click", closeDetailPanel)
  document.getElementById("detail-close").addEventListener("click", closeDetailPanel)
  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDetailPanel()
  })
}

// ---------------------------------------------------------------------------
// Busca
// ---------------------------------------------------------------------------

function setupSearch() {
  document.getElementById("search-input").addEventListener("input", (e) => {
    state.query = e.target.value
    renderTable()
  })
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

function init() {
  renderHero()
  renderMarquee()
  renderStats()
  renderPositionFilters()
  renderTable()
  setupTableSorting()
  setupDetailPanel()
  setupSearch()
  document.getElementById("footer-year").textContent = `© ${new Date().getFullYear()} — Feito para análise de dados esportivos.`
}

document.addEventListener("DOMContentLoaded", init)
