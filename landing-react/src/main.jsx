import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

const fallbackData = {
  brand: 'D-Webin Digital Invitation',
  logo_url: '/admin/assets/brand/d-webin-logo.svg',
  template_count: 0,
  groups: [],
};

function App() {
  const [data, setData] = useState(fallbackData);
  const [activeKey, setActiveKey] = useState('');
  const [query, setQuery] = useState('');
  const [expanded, setExpanded] = useState({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;
    fetch('/admin/templates_api.php', { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error('Gagal memuat katalog template');
        return res.json();
      })
      .then((payload) => {
        if (!alive) return;
        setData(payload);
        setActiveKey(payload.groups?.[0]?.key || '');
      })
      .catch(() => {
        if (alive) setData(fallbackData);
      })
      .finally(() => {
        if (alive) setLoading(false);
      });
    return () => {
      alive = false;
    };
  }, []);

  const groups = data.groups || [];
  const activeGroup = groups.find((group) => group.key === activeKey) || groups[0];
  const heroGroups = ['animation', 'minimalist', 'vintage']
    .map((key) => groups.find((group) => group.key === key))
    .filter(Boolean);

  const visibleTemplates = useMemo(() => {
    if (!activeGroup) return [];
    const term = query.trim().toLowerCase();
    const list = activeGroup.templates.filter((template) => template.name.toLowerCase().includes(term));
    return expanded[activeGroup.key] || term ? list : list.slice(0, 5);
  }, [activeGroup, expanded, query]);

  const hasMore = activeGroup && !expanded[activeGroup.key] && !query && activeGroup.templates.length > 5;

  return (
    <>
      <nav className="nav">
        <div className="wrap navInner">
          <a className="brand" href="#">
            <img src={data.logo_url} alt="D-Webin" />
            <span>D-WEBIN DIGITAL INVITATION</span>
          </a>
          <div className="navLinks">
            <a href="#fitur">Fitur</a>
            <a href="#template">Template</a>
            <a href="#harga">Harga</a>
          </div>
          <a className="navCta" href="#harga">Mulai Pesan</a>
        </div>
      </nav>

      <section className="hero">
        <div className="heroStage" aria-hidden="true">
          {heroGroups.map((group, index) => {
            const template = group.templates[0];
            return (
              <div className={`phoneShot shot${index + 1}`} key={group.key}>
                <div className="phoneUi">
                  <div className="phoneArt">
                    <img src={template?.thumbnail_url || data.logo_url} alt="" />
                  </div>
                  <div className="phoneCopy">
                    <b>{group.label}</b>
                    <span>{group.price_label}</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        <div className="wrap heroContent">
          <span className="eyebrow">Undangan digital siap sebar</span>
          <h1>D-Webin Digital Invitation</h1>
          <p>Landing undangan pernikahan yang elegan, cepat dibagikan, dan mudah dikelola dari dashboard. Katalog template kini berbasis React, ringan, dan siap menampung koleksi yang terus bertambah.</p>
          <div className="heroActions">
            <a className="btn primary" href="#template">Lihat Template</a>
            <a className="btn ghost" href="#harga">Cek Harga</a>
          </div>
          <div className="heroMetrics">
            <div><b>{loading ? '...' : data.template_count}</b><span>template siap pakai</span></div>
            <div><b>{groups.length || '...'}</b><span>kategori desain</span></div>
            <div><b>Unlimited</b><span>link tamu personal</span></div>
          </div>
        </div>
      </section>

      <section className="intro">
        <div className="wrap introGrid">
          <div>
            <span className="sectionKicker">Untuk acara yang berasa personal</span>
            <h2 className="sectionTitle">Bagikan undangan yang terlihat matang sejak link pertama dibuka.</h2>
          </div>
          <p className="lead">D-Webin membantu kamu membuat undangan digital dengan template premium, nama tamu otomatis, pengelolaan daftar tamu, dan export/import CSV untuk operasional yang lebih cepat.</p>
        </div>
      </section>

      <section id="fitur" className="band cream">
        <div className="wrap features">
          <Feature title="Nama tamu personal" text="Setiap tamu bisa menerima link dengan sapaan khusus." />
          <Feature title="Template per kategori" text="Pilih gaya Animation, Minimalist, Luxury, atau Vintage sesuai konsep acara." />
          <Feature title="Kelola tamu" text="Tambah manual, import CSV, export data, dan salin link personal." />
          <Feature title="Preview cepat" text="Landing hanya memuat thumbnail real, bukan iframe template berat." />
        </div>
      </section>

      <section id="template" className="band templatesBand">
        <div className="wrap">
          <span className="sectionKicker">Pilihan desain</span>
          <h2 className="sectionTitle">Template siap jalan</h2>
          <div className="templateToolbar">
            <div className="tabs" role="tablist" aria-label="Kategori template">
              {groups.map((group) => (
                <button
                  className={`tab ${group.key === activeGroup?.key ? 'active' : ''}`}
                  type="button"
                  key={group.key}
                  onClick={() => setActiveKey(group.key)}
                >
                  {group.label} <span>{group.price_label}</span>
                </button>
              ))}
            </div>
            <input
              className="search"
              type="search"
              placeholder="Cari template..."
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />
          </div>

          {activeGroup ? (
            <div className="templatePanel">
              <h3>{activeGroup.label} <span>{activeGroup.price_label}</span></h3>
              <div className="templateGrid">
                {visibleTemplates.map((template) => (
                  <TemplateCard template={template} key={template.key} />
                ))}
              </div>
              {visibleTemplates.length === 0 && <div className="empty">Template tidak ditemukan.</div>}
              {hasMore && (
                <button className="btn showMore" type="button" onClick={() => setExpanded({ ...expanded, [activeGroup.key]: true })}>
                  Lihat semua {activeGroup.templates.length} template
                </button>
              )}
            </div>
          ) : (
            <div className="empty">Katalog template sedang dimuat.</div>
          )}
        </div>
      </section>

      <section className="band">
        <div className="wrap">
          <span className="sectionKicker">Alur kerja</span>
          <h2 className="sectionTitle">Dari data acara ke link siap sebar.</h2>
          <div className="steps">
            <Feature title="1. Pilih template" text="Tentukan desain yang paling cocok dengan karakter acara." />
            <Feature title="2. Isi konten" text="Nama pasangan, tanggal, lokasi, foto, video, dan link peta." />
            <Feature title="3. Sebar link" text="Import daftar tamu, salin link personal, lalu kirim ke WhatsApp." />
          </div>
        </div>
      </section>

      <section id="harga" className="band cream">
        <div className="wrap">
          <span className="sectionKicker">Kategori & harga</span>
          <h2 className="sectionTitle">Harga mengikuti kategori template.</h2>
          <div className="packages">
            {groups.map((group) => (
              <article className={`package ${group.key === 'animation' ? 'highlight' : ''}`} key={group.key}>
                <b>{group.label}</b>
                <div className="price">{group.price_label}</div>
                <p>{group.template_count ? 'Template siap pakai dengan nama tamu personal dan dashboard pengelolaan.' : 'Kategori disiapkan untuk koleksi template berikutnya.'}</p>
                <div className="list"><div>Nama tamu personal</div><div>Kelola tamu + import CSV</div><div>Preview sebelum publish</div></div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="cta">
        <div className="wrap ctaGrid">
          <div><h2>Siap bikin undangan yang enak dibuka dan mudah disebar?</h2><p>Kirim brief acara, pilih template, lalu biarkan dashboard D-Webin yang merapikan sisanya.</p></div>
          <a className="btn primary" href="mailto:halo@d-webindigital.web.id?subject=Pesan%20Undangan%20Digital%20D-Webin">Konsultasi Sekarang</a>
        </div>
      </section>
      <footer className="footer"><div className="wrap"><span>D-Webin Digital Invitation</span><a href="/admin/login.php">Admin</a></div></footer>
    </>
  );
}

function Feature({ title, text }) {
  return <article className="feature"><b>{title}</b><p>{text}</p></article>;
}

function TemplateCard({ template }) {
  return (
    <article className="templateCard">
      <a className="templateThumb" target="_blank" rel="noopener noreferrer" href={template.preview_url}>
        <img src={template.thumbnail_url} alt={template.name} loading="lazy" />
      </a>
      <div className="templateInfo">
        <div><b>{template.name}</b><span>{template.price_label}</span></div>
        <a target="_blank" rel="noopener noreferrer" href={template.preview_url}>Buka Preview</a>
      </div>
    </article>
  );
}

createRoot(document.getElementById('root')).render(<App />);
