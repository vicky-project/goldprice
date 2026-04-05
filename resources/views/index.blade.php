@extends('coreui::layouts.mini-app')

@section('content')
<div class="container py-3">
  <div class="row justify-content-center mb-3">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('telegram.home') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
      </div>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="bi bi-gem2 me-2"></i>Harga Emas Dunia</h4>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-3">
              <label>Cari Mata Uang</label>
              <div class="input-group">
                <input type="text" id="currency-search" class="form-control" placeholder="Ketik nama atau kode mata uang...">
                <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Hapus pencarian">
                  <i class="bi bi-x-lg"></i> ✖
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <label>Mata Uang</label>
              <select id="currency-select" class="form-select">
                <option value="">-- Pilih Mata Uang --</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Rentang Waktu</label>
              <select id="range-select" class="form-select">
                <option value="6">6 jam terakhir</option>
                <option value="24">24 jam terakhir</option>
                <option value="7" selected>7 hari</option>
                <option value="30">30 hari</option>
                <option value="90">90 hari</option>
              </select>
            </div>
            <div class="col-md-3 align-self-end">
              <button id="refresh-btn" class="btn btn-primary">Refresh</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <h4>Tabel Harga Terkini</h4>
      <div id="loading-table" class="text-center d-none">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loding...</span>
        </div>
        <p>
          Memuat data...
        </p>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered" id="price-table">
          <thead>
            <tr><th>Mata Uang</th><th>Ounce</th><th>Gram</th><th>Tola</th><th>Update</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="col-md-6">
      <h4>Chart History</h4>
      <div style="position: relative;">
        <canvas id="price-chart" width="400" height="200"></canvas>
        <div id="loading-chart" class="text-center d-none" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
          <div class="spinner-border text-primary"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let chartInstance = null;
  let isLoading = false;
  const apiBase = '{{ secure_url(config("app.url")) }}/api/gold-price';

  // Mapping untuk mata uang fiat standar (3 huruf)
  const fiatCurrencies = {
    'AED': 'UAE Dirham',
    'AFN': 'Afghani',
    'ALL': 'Lek',
    'AMD': 'Armenian Dram',
    'ANG': 'Netherlands Antillean Guilder',
    'AOA': 'Kwanza',
    'ARS': 'Argentine Peso',
    'AUD': 'Australian Dollar',
    'AWG': 'Aruban Florin',
    'AZN': 'Azerbaijani Manat',
    'BAM': 'Convertible Mark',
    'BBD': 'Barbados Dollar',
    'BDT': 'Taka',
    'BGN': 'Bulgarian Lev',
    'BHD': 'Bahraini Dinar',
    'BIF': 'Burundi Franc',
    'BMD': 'Bermudian Dollar',
    'BND': 'Brunei Dollar',
    'BOB': 'Boliviano',
    'BRL': 'Brazilian Real',
    'BSD': 'Bahamian Dollar',
    'BTN': 'Ngultrum',
    'BWP': 'Pula',
    'BYN': 'Belarusian Ruble',
    'BZD': 'Belize Dollar',
    'CAD': 'Canadian Dollar',
    'CDF': 'Congolese Franc',
    'CHF': 'Swiss Franc',
    'CLP': 'Chilean Peso',
    'CNY': 'Yuan Renminbi',
    'COP': 'Colombian Peso',
    'CRC': 'Costa Rican Colon',
    'CUP': 'Cuban Peso',
    'CVE': 'Cabo Verde Escudo',
    'CZK': 'Czech Koruna',
    'DJF': 'Djibouti Franc',
    'DKK': 'Danish Krone',
    'DOP': 'Dominican Peso',
    'DZD': 'Algerian Dinar',
    'EGP': 'Egyptian Pound',
    'ERN': 'Nakfa',
    'ETB': 'Ethiopian Birr',
    'EUR': 'Euro',
    'FJD': 'Fiji Dollar',
    'FKP': 'Falkland Islands Pound',
    'FOK': 'Krone',
    'GBP': 'Pound Sterling',
    'GEL': 'Lari',
    'GGP': 'Pound Sterling',
    'GHS': 'Ghana Cedi',
    'GIP': 'Gibraltar Pound',
    'GMD': 'Dalasi',
    'GNF': 'Guinean Franc',
    'GTQ': 'Quetzal',
    'GYD': 'Guyana Dollar',
    'HKD': 'Hong Kong Dollar',
    'HNL': 'Lempira',
    'HTG': 'Gourde',
    'HUF': 'Forint',
    'IDR': 'Rupiah',
    'ILS': 'New Israeli Sheqel',
    'IMP': 'Pound Sterling',
    'INR': 'Indian Rupee',
    'IQD': 'Iraqi Dinar',
    'IRR': 'Iranian Rial',
    'ISK': 'Iceland Krona',
    'JEP': 'Pound Sterling',
    'JMD': 'Jamaican Dollar',
    'JOD': 'Jordanian Dinar',
    'JPY': 'Yen',
    'KES': 'Kenyan Shilling',
    'KGS': 'Som',
    'KHR': 'Riel',
    'KID': 'Kiribati Dollar',
    'KMF': 'Comorian Franc',
    'KRW': 'Won',
    'KWD': 'Kuwaiti Dinar',
    'KYD': 'Cayman Islands Dollar',
    'KZT': 'Tenge',
    'LAK': 'Lao Kip',
    'LBP': 'Lebanese Pound',
    'LKR': 'Sri Lanka Rupee',
    'LRD': 'Liberian Dollar',
    'LSL': 'Loti',
    'LYD': 'Libyan Dinar',
    'MAD': 'Moroccan Dirham',
    'MDL': 'Moldovan Leu',
    'MGA': 'Malagasy Ariary',
    'MKD': 'Denar',
    'MMK': 'Kyat',
    'MNT': 'Tugrik',
    'MOP': 'Pataca',
    'MRU': 'Ouguiya',
    'MUR': 'Mauritius Rupee',
    'MVR': 'Rufiyaa',
    'MWK': 'Malawi Kwacha',
    'MXN': 'Mexican Peso',
    'MYR': 'Malaysian Ringgit',
    'MZN': 'Mozambique Metical',
    'NAD': 'Namibia Dollar',
    'NGN': 'Naira',
    'NIO': 'Cordoba Oro',
    'NOK': 'Norwegian Krone',
    'NPR': 'Nepalese Rupee',
    'NZD': 'New Zealand Dollar',
    'OMR': 'Rial Omani',
    'PAB': 'Balboa',
    'PEN': 'Sol',
    'PGK': 'Kina',
    'PHP': 'Philippine Peso',
    'PKR': 'Pakistan Rupee',
    'PLN': 'Zloty',
    'PYG': 'Guarani',
    'QAR': 'Qatari Rial',
    'RON': 'Romanian Leu',
    'RSD': 'Serbian Dinar',
    'RUB': 'Russian Ruble',
    'RWF': 'Rwanda Franc',
    'SAR': 'Saudi Riyal',
    'SBD': 'Solomon Islands Dollar',
    'SCR': 'Seychelles Rupee',
    'SDG': 'Sudanese Pound',
    'SEK': 'Swedish Krona',
    'SGD': 'Singapore Dollar',
    'SHP': 'Saint Helena Pound',
    'SLE': 'Leone',
    'SOS': 'Somali Shilling',
    'SRD': 'Surinam Dollar',
    'SSP': 'South Sudanese Pound',
    'STN': 'Dobra',
    'SYP': 'Syrian Pound',
    'SZL': 'Lilangeni',
    'THB': 'Baht',
    'TJS': 'Somoni',
    'TMT': 'Turkmenistan New Manat',
    'TND': 'Tunisian Dinar',
    'TOP': 'Pa\'anga',
    'TRY': 'Turkish Lira',
    'TTD': 'Trinidad and Tobago Dollar',
    'TVD': 'Tuvalu Dollar',
    'TWD': 'New Taiwan Dollar',
    'TZS': 'Tanzanian Shilling',
    'UAH': 'Hryvnia',
    'UGX': 'Uganda Shilling',
    'USD': 'US Dollar',
    'UYU': 'Peso Uruguayo',
    'UZS': 'Uzbekistan Sum',
    'VES': 'Bolívar Soberano',
    'VND': 'Dong',
    'VUV': 'Vatu',
    'WST': 'Tala',
    'XAF': 'CFA Franc BEAC',
    'XCD': 'East Caribbean Dollar',
    'XDR': 'SDR',
    'XOF': 'CFA Franc BCEAO',
    'XPF': 'CFP Franc',
    'YER': 'Yemeni Rial',
    'ZAR': 'Rand',
    'ZMW': 'Zambian Kwacha',
    'ZWL': 'Zimbabwe Dollar'
  };

  // Mapping untuk cryptocurrency (kode 3-4 huruf)
  const cryptoCurrencies = {
    'BTC': 'Bitcoin',
    'ETH': 'Ethereum',
    'USDT': 'Tether',
    'BNB': 'Binance Coin',
    'XRP': 'Ripple',
    'ADA': 'Cardano',
    'SOL': 'Solana',
    'DOGE': 'Dogecoin',
    'DOT': 'Polkadot',
    'MATIC': 'Polygon',
    'SHIB': 'Shiba Inu',
    'TRX': 'Tron',
    'AVAX': 'Avalanche',
    'UNI': 'Uniswap',
    'LINK': 'Chainlink',
    'ETC': 'Ethereum Classic',
    'XLM': 'Stellar',
    'BCH': 'Bitcoin Cash',
    'ALGO': 'Algorand',
    'VET': 'VeChain',
    'ICP': 'Internet Computer',
    'FIL': 'Filecoin',
    'ATOM': 'Cosmos',
    'NEAR': 'NEAR Protocol',
    'EGLD': 'Elrond',
    'FTM': 'Fantom',
    'SAND': 'The Sandbox',
    'MANA': 'Decentraland',
    'AXS': 'Axie Infinity',
    'AAVE': 'Aave',
    'MKR': 'Maker',
    'COMP': 'Compound',
    'YFI': 'Yearn.finance',
    'ZEC': 'Zcash',
    'DASH': 'Dash',
    'XMR': 'Monero',
    'LTC': 'Litecoin',
    'EOS': 'EOS',
    'NEO': 'NEO',
    'QTUM': 'Qtum',
    'ZIL': 'Zilliqa',
    'ICX': 'ICON',
    'ONT': 'Ontology',
    'IOST': 'IOST',
    'THETA': 'Theta',
    'TFUEL': 'Theta Fuel',
    'ENJ': 'Enjin Coin',
    'CHZ': 'Chiliz',
    'BAT': 'Basic Attention Token',
    'ZRX': '0x',
    'KNC': 'Kyber Network',
    'REN': 'Ren',
    'CRV': 'Curve DAO',
    'SNX': 'Synthetix',
    'UMA': 'UMA',
    'BAL': 'Balancer',
    'LRC': 'Loopring',
    'GTC': 'Gitcoin',
    'ENS': 'Ethereum Name Service',
    'GRT': 'The Graph',
    'OCEAN': 'Ocean Protocol',
    'NMR': 'Numeraire',
    'ORCA': 'Orca',
    'RAY': 'Raydium',
    'SRM': 'Serum',
    'FTT': 'FTX Token',
    'KSM': 'Kusama',
    'MOVR': 'Moonriver',
    'GLMR': 'Moonbeam',
    'ACA': 'Acala',
    'KAR': 'Karura',
    'PHA': 'Phala',
    'RMRK': 'RMRK',
    'BSV': 'Bitcoin SV',
    'XEC': 'eCash',
    'XEM': 'NEM',
    'WAVES': 'Waves',
    'WAXP': 'WAX',
    'MIOTA': 'IOTA',
    'HOT': 'Holo',
    'NANO': 'Nano',
    'RVN': 'Ravencoin',
    'XVG': 'Verge',
    'SC': 'Siacoin',
    'STORJ': 'Storj',
    'GNT': 'Golem',
    'OMG': 'OmiseGO',
    'REP': 'Augur',
    'ANT': 'Aragon',
    'LPT': 'Livepeer',
    'CVC': 'Civic',
    'TRAC': 'OriginTrail',
    'COTI': 'COTI',
    'CELR': 'Celer Network',
    'PERP': 'Perpetual Protocol',
    'RLC': 'iExec RLC',
    'MIR': 'Mirror Protocol',
    'ANC': 'Anchor Protocol',
    'LUNA': 'Terra',
    'UST': 'TerraUSD',
    'MIM': 'Magic Internet Money',
    'FXS': 'Frax Share',
    'CVX': 'Convex Finance',
    'SPELL': 'Spell Token',
    'MLN': 'Enzyme',
    'DYDX': 'dYdX',
    'INJ': 'Injective',
    'AUDIO': 'Audius',
    'RAD': 'Radicle',
    'BOND': 'BarnBridge',
    'KP3R': 'Keep3rV1',
    'ALPHA': 'Alpha Finance',
    'BNT': 'Bancor',
    'STX': 'Stacks',
    'AR': 'Arweave',
    'HNT': 'Helium',
    'FLOW': 'Flow',
    'MINA': 'Mina',
    'CELO': 'Celo',
    'ROSE': 'Oasis Network',
    'ONE': 'Harmony',
    'KAVA': 'Kava',
    'SCRT': 'Secret',
    'FET': 'Fetch.ai',
    'AGIX': 'SingularityNET',
    'BAND': 'Band Protocol',
    'NKN': 'NKN',
    'ARDR': 'Ardor',
    'IGNIS': 'Ignis',
    'STRAT': 'Stratis',
    'ARK': 'Ark',
    'LSK': 'Lisk',
    'RISE': 'Rise',
    'SHIFT': 'Shift',
    'VOX': 'Voxels',
    'GAME': 'GameCredits',
    'PIRL': 'Pirl',
    'ETP': 'Metaverse ETP',
    'MUE': 'MonetaryUnit',
    'PART': 'Particl',
    'NAV': 'NavCoin',
    'VIA': 'Viacoin',
    'XZC': 'Zcoin',
    'ZEN': 'Horizen',
    'SYS': 'Syscoin',
    'BLOCK': 'Blocknet',
    'GRC': 'Gridcoin',
    'XPM': 'Primecoin',
    'PPC': 'Peercoin',
    'NMC': 'Namecoin',
    'FTC': 'Feathercoin',
    'WDC': 'Worldcoin',
    'CANN': 'CannabisCoin',
    'POT': 'PotCoin',
    'NLG': 'Gulden',
    'HTML': 'HTMLCOIN',
    'RDD': 'Reddcoin',
    'VTC': 'Vertcoin',
    'MONA': 'MonaCoin',
    'DGB': 'DigiByte',
    'GCR': 'Global Currency Reserve',
    'XDN': 'DigitalNote',
    'XMY': 'Myriad',
    'XST': 'Stealth',
    'BLK': 'BlackCoin',
    'CLAM': 'Clams',
    'FLO': 'FlorinCoin',
    'NEOS': 'NeosCoin',
    'QBC': 'Quebecoin',
    'RBY': 'Rubycoin',
    'TAG': 'TagCoin',
    'UNO': 'Unobtanium',
    'ZRC': 'ZrCoin'
  };

  // Gabungkan semua mapping
  const currencyNames = {
    ...fiatCurrencies,
    ...cryptoCurrencies
  };

  // Fungsi untuk mendapatkan nama mata uang (fallback ke kode jika tidak dikenal)
  function getCurrencyName(code) {
    return currencyNames[code] || code;
  }

  let allCurrencies = []; // Menyimpan semua kode mata uang

  // Fungsi untuk mengisi select berdasarkan filter
  function renderCurrencyOptions(filterText) {
    const select = document.getElementById('currency-select');
    const filter = filterText.toLowerCase().trim();
    const currentValue = select.value; // simpan pilihan sebelumnya jika ada

    // Filter berdasarkan nama atau kode
    const filtered = filter === '' ? allCurrencies: allCurrencies.filter(code => {
    const displayName = currencyNames[code] || code;
    return displayName.toLowerCase().includes(filter) || code.toLowerCase().includes(filter);
    });

    // Regenerasi opsi
    select.innerHTML = '<option value="">-- Pilih Mata Uang --</option>';
    filtered.forEach(code => {
    const opt = document.createElement('option');
    opt.value = code;
    opt.textContent = `${currencyNames[code] || code} (${code})`;
    select.appendChild(opt);
    });

    // Kembalikan pilihan sebelumnya jika masih tersedia
    if (currentValue && filtered.includes(currentValue)) {
      select.value = currentValue;
    } else if (filtered.length === 1 && filtered[0]) {
      // Opsional: auto-pilih jika hanya satu hasil
      // select.value = filtered[0];
    }
  }

  // Inisialisasi event listener untuk pencarian dan tombol clear
  function initCurrencySearch() {
    const searchInput = document.getElementById('currency-search');
    const clearBtn = document.getElementById('clear-search');

    searchInput.addEventListener('input', function() {
    renderCurrencyOptions(this.value);
    });

    clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    renderCurrencyOptions('');
    searchInput.focus();
    });
  }

  async function fetchCurrencies() {
    const res = await fetch(`${apiBase}/currencies`);
    allCurrencies = await res.json();
    renderCurrencyOptions('');
    initCurrencySearch();
  }

  async function fetchLatest(currency = '') {
    const url = currency ? `${apiBase}/latest?currency=${currency}`: `${apiBase}/latest`;
    const res = await fetch(url);
    return await res.json();
  }

  async function fetchHistory(currency, hours = null, days = 30) {
    let url = `${apiBase}/history?currency=${currency}`;
    if (hours) {
      url += `&hours=${hours}`;
    } else {
      url += `&days=${days}`;
    }
    const res = await fetch(url);
    return await res.json();
  }

  function renderTable(data) {
    const tbody = document.querySelector('#price-table tbody');
    tbody.innerHTML = '';
    data.forEach(item => {
    const currencyDisplay = getCurrencyName(item.currency);
    const row = `<tr>
    <td>${currencyDisplay} (${item.currency})</td>
    <td>${Number(item.ounce).toLocaleString()}</td>
    <td>${Number(item.gram).toLocaleString()}</td>
    <td>${Number(item.tola).toLocaleString()}</td>
    <td>${new Date(item.price_date).toLocaleString()}</td>
    </tr>`;
    tbody.insertAdjacentHTML('beforeend', row);
    });
  }

  function renderChart(history, unit = 'gram') {
    const ctx = document.getElementById('price-chart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    if (!history.length) {
      document.getElementById('price-chart').style.display = 'none';
      return;
    }
    document.getElementById('price-chart').style.display = 'block';

    const labels = history.map(h => {
    const dt = new Date(h.price_date);
    return dt.toLocaleString();
    });
    const prices = history.map(h => parseFloat(h[unit]));
    chartInstance = new Chart(ctx,
      {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: `Harga per ${unit}`,
            data: prices,
            borderColor: 'rgb(255, 193, 7)',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.2,
            pointRadius: 2,
            pointHoverRadius: 5
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `${context.dataset.label}: ${context.raw.toLocaleString()}`;
                }
              }
            },
            zoom: {
              zoom: {
                wheel: {
                  enabled: true
                },
                pinch: {
                  enabled: true
                },
                mode: 'x',
              }
            }
          },
          scales: {
            x: {
              title: {
                display: true, text: 'Waktu'
              },
              ticks: {
                maxRotation: 45,
                autoSkip: true,
                maxTicksLimit: 10
              }
            },
            y: {
              title: {
                display: true, text: `Harga (${unit})`
              },
              beginAtZero: false
            }
          }
        }
      });
  }

  async function loadAll() {
    if (isLoading) return;
    isLoading = true;

    const refreshBtn = document.getElementById('refresh-btn');
    const currencySelect = document.getElementById('currency-select');
    const rangeSelect = document.getElementById('range-select');

    const loadingTable = document.getElementById('loading-table');
    const loadingChart = document.getElementById('loading-chart');
    const priceChart = document.getElementById('price-chart');

    loadingTable.classList.remove('d-none');
    loadingChart.classList.remove('d-none');
    priceChart.style.opacity = "0.5";
    refreshBtn.disabled = true;
    currencySelect.disabled = true;
    rangeSelect.disabled = true;

    try {
      const currency = currencySelect.value;
      const rangeValue = parseInt(rangeSelect.value, 10);
      let hours = null,
      days = 30;
      if (rangeValue <= 24) {
        hours = rangeValue;
      } else {
        days = rangeValue;
      }
      const latestData = await fetchLatest(currency);
      renderTable(latestData);
      let targetCurrency = currency;
      if (!targetCurrency && latestData.length) {
        targetCurrency = latestData[0].currency;
        currencySelect.value = targetCurrency;
      }
      if (targetCurrency) {
        const history = await fetchHistory(targetCurrency, hours, days);
        if (history && history.length) {
          renderChart(history, 'gram');
        } else {
          alert("No history data found for: " + targetCurrency);
          priceChart.style.display = 'none';
        }
      }
    } catch (err) {
      console.error(err);
    } finally {
      loadingTable.classList.add('d-none');
      loadingChart.classList.add('d-none');
      priceChart.style.opacity = "1";
      refreshBtn.disabled = false;
      currencySelect.disabled = false;
      rangeSelect.disabled = false;
      isLoading = false;
    }
  }

  document.getElementById('refresh-btn').addEventListener('click', loadAll);
  document.getElementById('currency-select').addEventListener('change', loadAll);
  document.getElementById('range-select').addEventListener('change', loadAll);

  document.addEventListener('DOMContentLoaded', function() {
  fetchCurrencies().then(() => loadAll()).catch(err => alert(err.message));
  });
</script>
@endsection

@push('styles')
<style>
  /* Tema Telegram */
  body {
    background-color: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
  }
  .card {
    background-color: var(--tg-theme-secondary-bg-color);
    border: none;
  }
  .card-header {
    background-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
    border-bottom: none;
  }
  .btn-primary {
    background-color: var(--tg-theme-button-color);
    border-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
  }
  .btn-outline-primary {
    color: var(--tg-theme-button-color);
    border-color: var(--tg-theme-button-color);
  }
  .btn-outline-primary:hover {
    background-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
  }
  .btn-outline-secondary {
    color: var(--tg-theme-hint-color);
    border-color: var(--tg-theme-hint-color);
  }
  .btn-outline-secondary:hover {
    background-color: var(--tg-theme-hint-color);
    color: var(--tg-theme-button-text-color);
  }
  .text-muted {
    color: var(--tg-theme-text-color) !important;
  }
  .table {
    color: var(--tg-theme-text-color);
  }
  .table-hover tbody tr:hover {
    background-color: text-white;
  }
  .table td, .table th {
    border-color: var(--tg-theme-section-separator-color);
  }
</style>
@endpush