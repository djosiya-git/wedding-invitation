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
  const heroGroups = ['graduation', 'animation', 'minimalist']
    .map((key) => groups.find((group) => group.key === key))
    .filter(Boolean);
  const featuredTemplates = groups.flatMap((group) => group.templates.slice(0, 2)).slice(0, 6);

  const visibleTemplates = useMemo(() => {
    if (!activeGroup) return [];
    return expanded[activeGroup.key] ? activeGroup.templates : activeGroup.templates.slice(0, 5);
  }, [activeGroup, expanded]);

  const hasMore = activeGroup && !expanded[activeGroup.key] && activeGroup.templates.length > 5;

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
            <a href="#solusi">Solusi</a>
            <a href="#template">Template</a>
            <a href="#guestbook">Guestbook</a>
            <a href="#harga">Harga</a>
          </div>
          <a className="navCta" href="#harga">Konsultasi</a>
        </div>
      </nav>

      <section className="hero">
        <div className="motionBg" aria-hidden="true">
          <span />
          <span />
          <span />
          <span />
        </div>
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
          <span className="eyebrow">Platform undangan digital profesional</span>
          <h1>D-Webin Digital Invitation</h1>
          <p>Solusi undangan digital untuk pernikahan, wisuda, acara perusahaan, seminar, dan berbagai agenda resmi. Setiap undangan dapat dikelola melalui dashboard, dibagikan secara personal, serta dikembangkan dengan sistem guestbook web untuk proses scan dan validasi kehadiran.</p>
          <div className="heroActions">
            <a className="btn primary" href="#template">Lihat Template</a>
            <a className="btn ghost" href="#guestbook">Lihat Guestbook</a>
          </div>
          <div className="heroMetrics">
            <div><b>{loading ? '...' : data.template_count}</b><span>template siap pakai</span></div>
            <div><b>{groups.length || '...'}</b><span>kategori desain</span></div>
            <div><b>QR Scan</b><span>validasi kehadiran</span></div>
          </div>
          <div className="heroTicker" aria-label="Kategori template">
            {groups.map((group) => <span key={group.key}>{group.label} - {group.price_label}</span>)}
          </div>
        </div>
      </section>

      <section className="intro">
        <div className="wrap introGrid">
          <div>
            <span className="sectionKicker">Untuk berbagai kebutuhan acara</span>
            <h2 className="sectionTitle">Undangan digital yang rapi, personal, dan siap digunakan secara profesional.</h2>
          </div>
          <p className="lead">D-Webin membantu penyelenggara acara menyiapkan undangan digital dengan tampilan yang representatif, tautan personal untuk setiap tamu, pengelolaan data undangan, serta dukungan import dan export tamu untuk kebutuhan operasional.</p>
        </div>
      </section>

      <section id="fitur" className="band cream">
        <div className="wrap features">
          <Feature number="01" title="Tautan tamu personal" text="Setiap tamu dapat menerima tautan undangan dengan sapaan dan parameter undangan masing-masing." />
          <Feature number="02" title="Template per kategori" text="Katalog dapat dikelompokkan untuk pernikahan, graduation, corporate event, seminar, dan kategori acara lainnya." />
          <Feature number="03" title="Pengelolaan tamu" text="Admin dapat menambahkan data tamu, melakukan import CSV, export data, serta menyalin pesan undangan." />
          <Feature number="04" title="Siap dikembangkan" text="Sistem dapat dilengkapi guestbook web untuk scan QR undangan dan pencatatan kehadiran acara." />
        </div>
      </section>

      <section id="solusi" className="band solutionBand">
        <div className="wrap">
          <span className="sectionKicker">Kategori solusi</span>
          <h2 className="sectionTitle">Satu platform untuk beragam agenda.</h2>
          <div className="useCases">
            <UseCase label="01" title="Wedding Invitation" text="Undangan pernikahan digital dengan tampilan elegan, detail acara, galeri, peta lokasi, dan tautan tamu personal." />
            <UseCase label="02" title="Graduation Invitation" text="Undangan wisuda atau graduation ceremony dengan informasi institusi, jadwal acara, dan akses berbasis QR invitation pass." />
            <UseCase label="03" title="Corporate Event" text="Undangan untuk gathering, launching, anniversary perusahaan, dan agenda internal dengan identitas acara yang profesional." />
            <UseCase label="04" title="Seminar & Workshop" text="Undangan seminar, pelatihan, dan workshop dengan data peserta, sesi acara, lokasi, serta validasi kehadiran." />
          </div>
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
          </div>

          {activeGroup ? (
            <div className="templatePanel">
              <h3>{activeGroup.label} <span>{activeGroup.price_label}</span></h3>
              <div className="templateGrid">
                {visibleTemplates.map((template, index) => (
                  <TemplateCard template={template} index={index} key={template.key} />
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
            <Feature number="01" title="Pilih template" text="Tentukan kategori dan desain yang sesuai dengan kebutuhan acara." />
            <Feature number="02" title="Lengkapi konten" text="Masukkan informasi acara, jadwal, lokasi, media pendukung, dan pengaturan tamu." />
            <Feature number="03" title="Sebarkan undangan" text="Import daftar tamu, salin tautan personal, lalu kirimkan undangan melalui WhatsApp." />
          </div>
        </div>
      </section>

      <section id="guestbook" className="band guestbookBand">
        <div className="wrap guestbookGrid">
          <div>
            <span className="sectionKicker">Guestbook web</span>
            <h2 className="sectionTitle">Scan undangan untuk validasi kehadiran.</h2>
            <p className="lead">Fitur guestbook web disiapkan untuk membantu panitia memindai QR atau tautan undangan tamu saat acara berlangsung. Data check-in dapat digunakan untuk memastikan tamu terdaftar, memantau kehadiran, dan merapikan proses registrasi di lokasi acara.</p>
          </div>
          <div className="guestbookCard" aria-label="Ilustrasi guestbook web">
            <div className="scannerFrame">
              <span></span>
              <div className="qrMock">
                <i></i><i></i><i></i><i></i>
              </div>
            </div>
            <div className="scanRows">
              <b>Status Kehadiran</b>
              <p>Tamu terverifikasi melalui scan undangan.</p>
              <small>Guestbook web akan mendukung pencatatan check-in secara lebih tertata.</small>
            </div>
          </div>
        </div>
      </section>

      {featuredTemplates.length > 0 && (
        <section className="showcase">
          <div className="showcaseTrack" aria-hidden="true">
            {[...featuredTemplates, ...featuredTemplates].map((template, index) => (
              <img src={template.thumbnail_url} alt="" key={`${template.key}-${index}`} loading="lazy" />
            ))}
          </div>
        </section>
      )}

      <section id="harga" className="band cream">
        <div className="wrap">
          <span className="sectionKicker">Kategori & harga</span>
          <h2 className="sectionTitle">Harga mengikuti kategori template.</h2>
          <div className="packages">
            {groups.map((group) => (
              <article className={`package ${group.key === 'animation' ? 'highlight' : ''}`} key={group.key}>
                <b>{group.label}</b>
                <div className="price">{group.price_label}</div>
                <p>{group.template_count ? 'Template siap digunakan dengan tautan tamu personal dan dashboard pengelolaan.' : 'Kategori disiapkan untuk koleksi template berikutnya.'}</p>
                <div className="list"><div>Tautan tamu personal</div><div>Kelola tamu + import CSV</div><div>Preview sebelum publish</div></div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="cta">
        <div className="wrap ctaGrid">
          <div><h2>Siap menyiapkan undangan digital untuk acara berikutnya?</h2><p>Kirim kebutuhan acara, pilih kategori template, dan D-Webin akan membantu menyiapkan undangan digital yang rapi, personal, serta siap dikembangkan dengan guestbook web.</p></div>
          <a className="btn primary" href="mailto:halo@d-webindigital.web.id?subject=Konsultasi%20Undangan%20Digital%20D-Webin">Konsultasi Sekarang</a>
        </div>
      </section>
      <footer className="footer"><div className="wrap"><span>D-Webin Digital Invitation</span><a href="/admin/login.php">Admin</a></div></footer>
    </>
  );
}

function Feature({ number, title, text }) {
  return <article className="feature">{number && <span>{number}</span>}<b>{title}</b><p>{text}</p></article>;
}

function UseCase({ label, title, text }) {
  return <article className="useCase"><span>{label}</span><b>{title}</b><p>{text}</p></article>;
}

function TemplateCard({ template, index }) {
  return (
    <article className="templateCard" style={{ '--delay': `${index * 70}ms` }}>
      <a className="templateThumb" target="_blank" rel="noopener noreferrer" href={template.preview_url}>
        {template.has_discount && <span className="discountRibbon">Promo</span>}
        <img src={template.thumbnail_url} alt={template.name} loading="lazy" />
      </a>
      <div className="templateInfo">
        <div>
          <b>{template.name}</b>
          <span className={template.has_discount ? 'salePrice' : ''}>
            {template.has_discount && <em>{template.base_price_label}</em>}
            {template.price_label}
          </span>
          {template.has_discount && <small>{template.discount_label}</small>}
        </div>
        <a target="_blank" rel="noopener noreferrer" href={template.preview_url}>Buka Preview</a>
      </div>
    </article>
  );
}

createRoot(document.getElementById('root')).render(<App />);
