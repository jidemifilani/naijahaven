/* VELOCITY MOTORS — 3D tilt, parallax, reveal & UI interactions */
document.addEventListener('DOMContentLoaded', () => {

  /* Mobile nav */
  const burger = document.getElementById('burger');
  const links = document.getElementById('navLinks');
  if (burger && links) burger.addEventListener('click', () => links.classList.toggle('open'));

  /* Scroll reveal */
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('show'); io.unobserve(e.target); } });
  }, { threshold: .12 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  /* 3D tilt on car cards & stat cards */
  const tiltEls = document.querySelectorAll('.car-card, .stat-card, .tilt');
  const maxTilt = 8;
  tiltEls.forEach(card => {
    card.addEventListener('mousemove', ev => {
      const r = card.getBoundingClientRect();
      const x = (ev.clientX - r.left) / r.width - .5;
      const y = (ev.clientY - r.top) / r.height - .5;
      card.style.transform = `perspective(900px) rotateY(${x * maxTilt}deg) rotateX(${-y * maxTilt}deg) translateY(-4px)`;
    });
    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
  });

  /* Hero card mouse parallax */
  const heroCard = document.querySelector('.hero-card');
  if (heroCard && matchMedia('(min-width:1000px)').matches) {
    document.addEventListener('mousemove', ev => {
      const x = ev.clientX / innerWidth - .5;
      const y = ev.clientY / innerHeight - .5;
      heroCard.style.transform = `rotateY(${-8 + x * 10}deg) rotateX(${4 - y * 8}deg)`;
    });
  }

  /* Rental price calculator (book.php) */
  const start = document.getElementById('start_date');
  const end = document.getElementById('end_date');
  const out = document.getElementById('priceOut');
  if (start && end && out) {
    const rate = parseFloat(out.dataset.rate || '0');
    const cur = out.dataset.currency || '$';
    const today = new Date().toISOString().split('T')[0];
    start.min = today;
    const calc = () => {
      if (start.value) end.min = start.value;
      const s = new Date(start.value), e2 = new Date(end.value);
      const days = Math.round((e2 - s) / 86400000);
      if (start.value && end.value && days > 0) {
        const unit = out.dataset.unit || 'day';
        out.innerHTML = `${days} ${unit}${days > 1 ? 's' : ''} × ${cur}${rate.toLocaleString()} = <b>${cur}${(days * rate).toLocaleString()}</b>`;
      } else { out.textContent = 'Select your dates to see the total.'; }
    };
    start.addEventListener('change', calc);
    end.addEventListener('change', calc);
    calc();
  }

  /* Image fallback: hide broken images so gradient shows */
  document.querySelectorAll('.car-media img, .detail-img img, .hero-card img').forEach(img => {
    img.addEventListener('error', () => { img.style.display = 'none'; });
  });

  /* Property photo gallery: click a thumb to swap the main image (property.php) */
  const mainImg = document.querySelector('.detail-img img');
  document.querySelectorAll('.gallery-thumbs img').forEach(thumb => {
    thumb.addEventListener('click', () => {
      if (!mainImg) return;
      mainImg.src = thumb.dataset.full;
      document.querySelectorAll('.gallery-thumbs img').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });
});
