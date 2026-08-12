type Product = {
  slug: string;
  name: string;
  customization_mode: string;
};

type OptionValue = { code: string; label: string; is_active?: boolean };
type OptionGroup = { code: string; name: string; values: OptionValue[] };
type Sku = {
  sku_code: string;
  variant_key: string;
  price_minor: number;
  availability: { available_for_checkout: boolean; requires_quote: boolean };
};
type Options = {
  product: { slug: string; name: string; currency: string };
  option_groups: OptionGroup[];
  print_positions: Array<{ code: string; label: string }>;
  print_methods: Array<{ code: string; label: string }>;
  skus: Sku[];
  validation: { print_method_compatibility: Record<string, string[]> };
};

const studio = document.querySelector<HTMLElement>('[data-mockup-studio]');

if (studio) {
  const products = JSON.parse(document.querySelector('#mockup-products-data')?.textContent || '[]') as Product[];
  const form = studio.querySelector<HTMLFormElement>('[data-studio-form]');
  const canvas = studio.querySelector<HTMLCanvasElement>('[data-mockup-canvas]');
  const ctx = canvas?.getContext('2d');

  if (form && canvas && ctx && products.length) {
    const apiBase = (studio.dataset.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '');
    const productSelect = form.querySelector<HTMLSelectElement>('[data-product-select]')!;
    const colorOptions = form.querySelector<HTMLElement>('[data-color-options]')!;
    const sizeSelect = form.querySelector<HTMLSelectElement>('[data-size-select]')!;
    const positionOptions = form.querySelector<HTMLElement>('[data-position-options]')!;
    const fileInput = form.querySelector<HTMLInputElement>('[data-file-input]')!;
    const fileName = form.querySelector<HTMLElement>('[data-file-name]')!;
    const scaleInput = form.querySelector<HTMLInputElement>('[data-scale-input]')!;
    const scaleOutput = form.querySelector<HTMLOutputElement>('[data-scale-output]')!;
    const errorBox = form.querySelector<HTMLElement>('[data-form-error]')!;
    const liveStatus = form.querySelector<HTMLElement>('[data-live-status]')!;
    const generateButton = form.querySelector<HTMLButtonElement>('[data-generate-button]')!;
    const generateLabel = form.querySelector<HTMLElement>('[data-generate-label]')!;
    const generatedImage = studio.querySelector<HTMLImageElement>('[data-generated-preview]')!;
    const generatedActions = form.querySelector<HTMLElement>('[data-generated-actions]')!;
    const addCartButton = form.querySelector<HTMLButtonElement>('[data-add-cart]')!;
    const previewBadge = studio.querySelector<HTMLElement>('[data-preview-badge]')!;
    const colorFieldset = form.querySelector<HTMLFieldSetElement>('[data-color-fieldset]')!;

    let options: Options | null = null;
    let artwork: HTMLImageElement | null = null;
    let artworkFile: File | null = null;
    let artworkUrl: string | null = null;
    let placement = { x: 50, y: 50, scale: 1 };
    let dragging = false;
    let customizationSnapshot: Record<string, unknown> | null = null;
    const watermarkLogo = new Image();
    watermarkLogo.src = '/brand/okina-watermark-mark.png';
    watermarkLogo.addEventListener('load', () => draw());

    const escapeHtml = (value: string) => value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char] || char);
    const colorHex = (code: string) => ({ ink: '#232324', black: '#232324', paper: '#eeeae0', white: '#f4f2ed', navy: '#1e2d4a', red: '#a6272a', royal: '#27539a', blue: '#27539a', green: '#2b5942', maroon: '#642230', yellow: '#dcb030', gold: '#dcb030' } as Record<string, string>)[code.toLowerCase()] || '#746f69';
    const selectedColor = () => form.querySelector<HTMLInputElement>('input[name="colour"]:checked')?.value || 'ink';
    const selectedPosition = () => form.querySelector<HTMLInputElement>('input[name="position"]:checked')?.value || 'front';
    const setError = (message = '') => {
      errorBox.textContent = message;
      errorBox.classList.toggle('hidden', !message);
    };
    const setStatus = (message: string) => { liveStatus.textContent = message; };
    const markEdited = () => {
      customizationSnapshot = null;
      generatedImage.hidden = true;
      generatedActions.hidden = true;
      previewBadge.textContent = 'Editing';
    };

    const printArea = () => {
      const position = selectedPosition();
      if (position === 'left_chest') return { x: 330, y: 305, width: 105, height: 135 };
      if (position === 'right_chest') return { x: 465, y: 305, width: 105, height: 135 };
      if (position === 'sleeve') return { x: 205, y: 315, width: 105, height: 125 };
      if (position === 'back') return { x: 285, y: 255, width: 330, height: 390 };
      return { x: 290, y: 270, width: 320, height: 360 };
    };

    const roundedRect = (x: number, y: number, width: number, height: number, radius: number) => {
      ctx.beginPath();
      ctx.roundRect(x, y, width, height, radius);
      ctx.fill();
    };

    const templateImages: Record<string, HTMLImageElement> = {};
    const getTemplateImage = (color: string) => {
      const key = color.toLowerCase();
      if (!templateImages[key]) {
        const img = new Image();
        img.src = `/mockups/tshirt-${key}.png`;
        img.addEventListener('load', () => draw());
        img.addEventListener('error', () => {
          if (key !== 'black' && key !== 'ink') {
            img.src = '/mockups/tshirt-black.png';
          }
        });
        templateImages[key] = img;
      }
      return templateImages[key];
    };

    const drawShirt = () => {
      const color = selectedColor();
      const templateImg = getTemplateImage(color);

      ctx.clearRect(0, 0, 900, 900);
      ctx.fillStyle = '#ebe7df';
      ctx.fillRect(0, 0, 900, 900);
      ctx.fillStyle = '#f8f6f1';
      roundedRect(28, 28, 844, 844, 26);

      if (templateImg && templateImg.complete && templateImg.naturalWidth) {
        ctx.drawImage(templateImg, 112, 90, 675, 510);
      } else {
        const base = colorHex(color);
        ctx.save();
        ctx.shadowColor = 'rgba(30, 25, 22, .22)';
        ctx.shadowBlur = 34;
        ctx.shadowOffsetY = 22;
        const shirt = new Path2D('M318 176 L238 210 L126 342 L230 414 L285 354 L303 734 L347 774 L553 774 L597 734 L615 354 L670 414 L774 342 L662 210 L582 176 C557 221 514 244 450 244 C386 244 343 221 318 176 Z');
        ctx.fillStyle = base;
        ctx.fill(shirt);
        ctx.restore();

        const shade = ctx.createLinearGradient(250, 250, 650, 760);
        shade.addColorStop(0, 'rgba(255,255,255,.2)');
        shade.addColorStop(.48, 'rgba(255,255,255,0)');
        shade.addColorStop(1, 'rgba(0,0,0,.22)');
        ctx.fillStyle = shade;
        ctx.fill(shirt);

        ctx.fillStyle = '#f8f6f1';
        ctx.beginPath();
        ctx.ellipse(450, 187, 73, 50, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,.2)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(305, 731);
        ctx.lineTo(595, 731);
        ctx.stroke();
      }
    };

    const artworkBounds = () => {
      const area = printArea();
      if (!artwork) return { x: area.x, y: area.y, width: 0, height: 0 };
      const fit = Math.min((area.width * .78 * placement.scale) / artwork.naturalWidth, (area.height * .78 * placement.scale) / artwork.naturalHeight);
      const width = artwork.naturalWidth * fit;
      const height = artwork.naturalHeight * fit;
      const centerX = area.x + (placement.x / 100) * area.width;
      const centerY = area.y + (placement.y / 100) * area.height;
      return {
        x: Math.max(area.x, Math.min(area.x + area.width - width, centerX - width / 2)),
        y: Math.max(area.y, Math.min(area.y + area.height - height, centerY - height / 2)),
        width,
        height,
      };
    };

    const draw = () => {
      drawShirt();
      const area = printArea();
      ctx.save();
      ctx.strokeStyle = 'rgba(217,45,45,.55)';
      ctx.lineWidth = 2;
      ctx.setLineDash([9, 8]);
      ctx.strokeRect(area.x, area.y, area.width, area.height);
      ctx.restore();

      if (artwork) {
        const bounds = artworkBounds();
        ctx.drawImage(artwork, bounds.x, bounds.y, bounds.width, bounds.height);
      } else {
        ctx.fillStyle = 'rgba(255,255,255,.78)';
        roundedRect(area.x + 18, area.y + area.height / 2 - 30, area.width - 36, 60, 6);
        ctx.fillStyle = '#786f68';
        ctx.font = '800 15px Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('YOUR ARTWORK HERE', area.x + area.width / 2, area.y + area.height / 2 + 5);
      }

      ctx.save();
      ctx.translate(450, 450);
      ctx.rotate(-Math.PI / 8);
      ctx.font = '900 19px Segoe UI, sans-serif';
      ctx.fillStyle = 'rgba(148, 35, 35, .2)';
      ctx.textAlign = 'center';
      for (let y = -520; y <= 520; y += 125) {
        for (let x = -650; x <= 650; x += 300) {
          if (watermarkLogo.complete && watermarkLogo.naturalWidth) {
            ctx.globalAlpha = .18;
            ctx.drawImage(watermarkLogo, x - 120, y - 27, 44, 44);
            ctx.globalAlpha = 1;
          }
          ctx.fillText('OKINA CRAFT / PREVIEW', x + 24, y);
        }
      }
      ctx.restore();
      ctx.fillStyle = 'rgba(24,21,19,.58)';
      ctx.fillRect(0, 420, 900, 68);
      if (watermarkLogo.complete && watermarkLogo.naturalWidth) {
        ctx.globalAlpha = .92;
        ctx.drawImage(watermarkLogo, 296, 425, 58, 58);
        ctx.globalAlpha = 1;
      }
      ctx.fillStyle = 'rgba(255,255,255,.92)';
      ctx.font = '900 26px Segoe UI, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('PROTECTED PREVIEW', 492, 461);
    };

    const optionGroup = (code: string) => options?.option_groups.find((group) => group.code === code);
    const renderOptions = () => {
      if (!options) return;
      const colors = optionGroup('color')?.values.filter((value) => value.is_active !== false) || [{ code: 'ink', label: 'Ink Black' }];
      const sizes = optionGroup('size')?.values.filter((value) => value.is_active !== false) || [];
      colorOptions.innerHTML = colors.map((color, index) => `<div class="colour-choice"><input id="colour-${escapeHtml(color.code)}" type="radio" name="colour" value="${escapeHtml(color.code)}" ${index === 0 ? 'checked' : ''}><label for="colour-${escapeHtml(color.code)}"><span class="colour-swatch" style="--swatch:${colorHex(color.code)}"></span>${escapeHtml(color.label)}</label></div>`).join('');
      sizeSelect.innerHTML = sizes.map((size) => `<option value="${escapeHtml(size.code)}">${escapeHtml(size.label)}</option>`).join('');
      positionOptions.innerHTML = options.print_positions.map((position, index) => `<div class="position-choice"><input id="position-${escapeHtml(position.code)}" type="radio" name="position" value="${escapeHtml(position.code)}" ${index === 0 ? 'checked' : ''}><label for="position-${escapeHtml(position.code)}">${escapeHtml(position.label)}</label></div>`).join('');
      colorFieldset.hidden = colors.length === 0;
      draw();
    };

    const loadOptions = async () => {
      setError();
      setStatus('Loading product options…');
      generateButton.disabled = true;
      try {
        const response = await fetch(`${apiBase}/catalog/products/${encodeURIComponent(productSelect.value)}/customization-options`, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        if (!response.ok) throw new Error(json?.message || 'Product options could not be loaded.');
        options = json.data as Options;
        renderOptions();
        markEdited();
        setStatus(artwork ? 'Artwork ready. Adjust placement, then generate.' : 'Choose your options and upload artwork.');
      } catch (error) {
        setError(error instanceof Error ? error.message : 'Product options could not be loaded.');
      } finally {
        generateButton.disabled = false;
      }
    };

    const validateArtwork = async (file: File) => {
      if (file.type !== 'image/png' && !file.name.toLowerCase().endsWith('.png')) throw new Error('Upload a PNG file with a transparent background.');
      if (file.size > 5 * 1024 * 1024) throw new Error('Artwork must be 5 MB or smaller.');
      const url = URL.createObjectURL(file);
      const image = new Image();
      await new Promise<void>((resolve, reject) => { image.onload = () => resolve(); image.onerror = () => reject(new Error('This PNG could not be read.')); image.src = url; });
      if (image.naturalWidth < 400 || image.naturalHeight < 400) { URL.revokeObjectURL(url); throw new Error('Artwork must be at least 400 × 400 pixels.'); }
      const sample = document.createElement('canvas');
      sample.width = Math.min(image.naturalWidth, 220);
      sample.height = Math.min(image.naturalHeight, 220);
      const sampleContext = sample.getContext('2d', { willReadFrequently: true });
      sampleContext?.drawImage(image, 0, 0, sample.width, sample.height);
      const pixels = sampleContext?.getImageData(0, 0, sample.width, sample.height).data;
      let transparent = false;
      if (pixels) for (let index = 3; index < pixels.length; index += 4) if (pixels[index] < 250) { transparent = true; break; }
      if (!transparent) { URL.revokeObjectURL(url); throw new Error('This PNG has no transparent background. Remove the background and upload it again.'); }
      return { image, url };
    };

    const selectedOptions = () => ({ color: selectedColor(), size: sizeSelect.value });
    const currentSku = () => {
      const key = Object.entries(selectedOptions()).filter(([, value]) => value).sort(([a], [b]) => a.localeCompare(b)).map(([code, value]) => `${code}:${value}`).join('|');
      return options?.skus.find((sku) => sku.variant_key === key) || null;
    };
    const selectedMethod = () => {
      const allowed = options?.validation.print_method_compatibility[selectedPosition()] || [];
      return allowed.includes('dtf') ? 'dtf' : allowed[0] || options?.print_methods[0]?.code || 'dtf';
    };

    fileInput.addEventListener('change', async () => {
      const file = fileInput.files?.[0];
      setError();
      if (!file) return;
      try {
        const validated = await validateArtwork(file);
        if (artworkUrl) URL.revokeObjectURL(artworkUrl);
        artwork = validated.image;
        artworkUrl = validated.url;
        artworkFile = file;
        fileName.textContent = file.name;
        placement = { x: 50, y: 50, scale: 1 };
        scaleInput.value = '100';
        scaleOutput.value = '100%';
        markEdited();
        draw();
        setStatus('Artwork ready. Drag it on the preview or use the position buttons.');
      } catch (error) {
        fileInput.value = '';
        setError(error instanceof Error ? error.message : 'Artwork could not be loaded.');
      }
    });

    productSelect.addEventListener('change', loadOptions);
    form.addEventListener('change', (event) => {
      if (event.target === productSelect || event.target === fileInput) return;
      markEdited();
      draw();
    });
    scaleInput.addEventListener('input', () => {
      placement.scale = Number(scaleInput.value) / 100;
      scaleOutput.value = `${scaleInput.value}%`;
      markEdited();
      draw();
    });
    form.querySelectorAll<HTMLButtonElement>('[data-nudge]').forEach((button) => button.addEventListener('click', () => {
      const direction = button.dataset.nudge;
      if (direction === 'up') placement.y = Math.max(10, placement.y - 3);
      if (direction === 'down') placement.y = Math.min(90, placement.y + 3);
      if (direction === 'left') placement.x = Math.max(10, placement.x - 3);
      if (direction === 'right') placement.x = Math.min(90, placement.x + 3);
      markEdited();
      draw();
      setStatus(`Artwork position: ${Math.round(placement.x)}% across, ${Math.round(placement.y)}% down.`);
    }));
    form.querySelector<HTMLButtonElement>('[data-reset]')?.addEventListener('click', () => {
      placement = { x: 50, y: 50, scale: 1 };
      scaleInput.value = '100';
      scaleOutput.value = '100%';
      markEdited();
      draw();
      setStatus('Artwork placement reset.');
    });
    form.querySelector<HTMLButtonElement>('[data-remove]')?.addEventListener('click', () => {
      if (artworkUrl) URL.revokeObjectURL(artworkUrl);
      artwork = null;
      artworkFile = null;
      artworkUrl = null;
      fileInput.value = '';
      fileName.textContent = 'Choose a transparent PNG';
      markEdited();
      draw();
      setStatus('Artwork removed.');
    });

    const pointerPlacement = (event: PointerEvent) => {
      const bounds = canvas.getBoundingClientRect();
      const canvasX = ((event.clientX - bounds.left) / bounds.width) * canvas.width;
      const canvasY = ((event.clientY - bounds.top) / bounds.height) * canvas.height;
      const area = printArea();
      placement.x = Math.max(10, Math.min(90, ((canvasX - area.x) / area.width) * 100));
      placement.y = Math.max(10, Math.min(90, ((canvasY - area.y) / area.height) * 100));
      markEdited();
      draw();
    };
    canvas.addEventListener('pointerdown', (event) => {
      if (!artwork) return;
      dragging = true;
      canvas.setPointerCapture(event.pointerId);
      pointerPlacement(event);
    });
    canvas.addEventListener('pointermove', (event) => { if (dragging) pointerPlacement(event); });
    canvas.addEventListener('pointerup', (event) => {
      dragging = false;
      if (canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId);
      setStatus('Placement updated. Generate the protected preview when ready.');
    });

    const responseMessage = (json: any, fallback: string) => {
      const errors = json?.errors ? Object.values(json.errors).flat().join(' ') : '';
      return errors || json?.message || fallback;
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      setError();
      const sku = currentSku();
      if (!artworkFile) { setError('Upload your transparent PNG before generating a preview.'); fileInput.focus(); return; }
      if (!sku?.availability.available_for_checkout) { setError('This colour and size combination is not available.'); sizeSelect.focus(); return; }
      generateButton.disabled = true;
      generateLabel.textContent = 'Securing artwork…';
      setStatus('Uploading your clean artwork privately…');
      try {
        const upload = new FormData();
        upload.append('design_file', artworkFile);
        upload.append('sku_code', sku.sku_code);
        Object.entries(selectedOptions()).forEach(([code, value]) => upload.append(`selected_options[${code}]`, value));
        upload.append('print_position', selectedPosition());
        upload.append('print_method', selectedMethod());
        upload.append('placement[x]', String(placement.x));
        upload.append('placement[y]', String(placement.y));
        upload.append('placement[scale]', String(placement.scale));
        upload.append('placement[rotation]', '0');
        const uploadResponse = await fetch(`${apiBase}/catalog/products/${encodeURIComponent(productSelect.value)}/design-upload`, { method: 'POST', body: upload, credentials: 'include', headers: { Accept: 'application/json' } });
        if ([401, 403, 419].includes(uploadResponse.status)) throw new Error(`SIGN_IN:${apiBase.replace(/\/api$/, '/login')}`);
        const uploadJson = await uploadResponse.json();
        if (!uploadResponse.ok) throw new Error(responseMessage(uploadJson, 'Artwork could not be uploaded.'));

        generateLabel.textContent = 'Applying watermark…';
        setStatus('Creating a protected PNG. This may take a moment…');
        const sourceId = uploadJson.data.file.public_id;
        const mockupResponse = await fetch(`${apiBase}/catalog/products/${encodeURIComponent(productSelect.value)}/protected-mockup/${encodeURIComponent(sourceId)}`, {
          method: 'POST',
          credentials: 'include',
          headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ color_code: selectedColor(), print_position: selectedPosition(), placement }),
        });
        const mockupJson = await mockupResponse.json();
        if (!mockupResponse.ok) throw new Error(responseMessage(mockupJson, 'Protected preview could not be generated.'));
        const snapshot = uploadJson.data.customization_snapshot;
        snapshot.files = [...(snapshot.files || []), mockupJson.data.file];
        snapshot.mockup_preview = { role: 'protected_mockup', render_type: 'server_png', source_file_public_id: sourceId, placement };
        customizationSnapshot = snapshot;
        generatedImage.src = mockupJson.data.preview_url;
        generatedImage.hidden = false;
        generatedActions.hidden = false;
        previewBadge.textContent = 'Protected PNG';
        setStatus('Protected preview generated and saved.');
        generatedActions.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Something went wrong.';
        if (message.startsWith('SIGN_IN:')) {
          setError('Sign in before uploading private artwork. Redirecting to login…');
          window.setTimeout(() => { window.location.href = message.slice(8); }, 900);
        } else setError(message);
      } finally {
        generateButton.disabled = false;
        generateLabel.textContent = 'Generate protected preview';
      }
    });

    addCartButton.addEventListener('click', async () => {
      const sku = currentSku();
      if (!customizationSnapshot || !sku) { setError('Generate the protected preview before adding this item.'); return; }
      addCartButton.disabled = true;
      addCartButton.textContent = 'Adding to cart…';
      setError();
      try {
        const response = await fetch(`${apiBase}/cart/items`, {
          method: 'POST',
          credentials: 'include',
          headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_slug: productSelect.value, sku_code: sku.sku_code, quantity: 1, customization_snapshot: customizationSnapshot }),
        });
        const json = await response.json();
        if (!response.ok) throw new Error(responseMessage(json, 'The customized item could not be added.'));
        setStatus('Added to cart. Redirecting…');
        window.setTimeout(() => { window.location.href = '/cart'; }, 500);
      } catch (error) {
        setError(error instanceof Error ? error.message : 'The customized item could not be added.');
        addCartButton.disabled = false;
        addCartButton.textContent = 'Add customized item to cart';
      }
    });

    draw();
    loadOptions();
  }
}
