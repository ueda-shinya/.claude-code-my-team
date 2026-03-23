( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const { __ } = wp.i18n;
  const {
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    InnerBlocks,
    useBlockProps
  } = wp.blockEditor || wp.editor;
  const { PanelBody, SelectControl, TextControl, Button, Notice, __experimentalNumberControl: NumberControl } = wp.components;
  const el = wp.element.createElement;

  // ---------- 子ブロック：手順 ----------
  registerBlockType('officeueda/manual-step', {
    apiVersion: 2,
    title: __('調理手順（1ステップ）', 'cook-manual'),
    icon: 'feedback',
    category: 'common',
    parent: ['officeueda/manual-steps'],
    supports: { reusable: false, html: false },
    attributes: {
      stepTitle: { type: 'string', default: '' },
      mediaType: { type: 'string', default: 'image' }, // image | video
      imageUrl:  { type: 'string', default: '' },
      imageAlt:  { type: 'string', default: '' },
      videoUrl:  { type: 'string', default: '' },
      // 本文は .p-step__body 自体のHTMLを保存
      body:      { type: 'string', source: 'html', selector: '.p-step__body' },
      noteImportant: { type: 'string', default: '' }, // ラベルは「ポイント」に変更
      noteWarning:   { type: 'string', default: '' }, // ラベルは「注意事項（警告）」
      timerSeconds:  { type: 'number', default: 0 },
    },

    edit: function( props ) {
      const { attributes, setAttributes } = props;
      const blockProps = useBlockProps({ className: 'p-step' });

      function onSelectImage( media ) {
        if (!media || !media.url) return;
        setAttributes({ imageUrl: media.url, imageAlt: media.alt || '' });
      }

      return el('div', blockProps,
        el('h3', { className: 'p-step__title' },
          el('span', { className: 'p-step__num' }, '#'),
          attributes.stepTitle || __('手順タイトル（未設定）', 'cook-manual')
        ),

        el('div', { className: 'p-step__inner' },

          // 左：メディア
          el('div', { className: 'p-step__media' },
            el(InspectorControls, {},
              el(PanelBody, { title: __('ステップ設定', 'cook-manual'), initialOpen: true },
                el(TextControl, {
                  label: __('手順タイトル', 'cook-manual'),
                  value: attributes.stepTitle,
                  onChange: v => setAttributes({ stepTitle: v })
                }),
                el(SelectControl, {
                  label: __('メディア種別', 'cook-manual'),
                  value: attributes.mediaType,
                  options: [
                    { label: __('画像', 'cook-manual'), value: 'image' },
                    { label: __('動画', 'cook-manual'), value: 'video' },
                  ],
                  onChange: v => setAttributes({ mediaType: v })
                }),
                attributes.mediaType === 'image' && el(MediaUploadCheck, {},
                  el(MediaUpload, {
                    onSelect: onSelectImage,
                    allowedTypes: ['image'],
                    value: attributes.imageUrl,
                    render: ({ open }) => el('div', {},
                      el(Button, { onClick: open, variant: 'primary' }, attributes.imageUrl ? __('画像を変更', 'cook-manual') : __('画像を選択', 'cook-manual')),
                      attributes.imageUrl && el('div', { style: { marginTop: '8px' } },
                        el('img', { src: attributes.imageUrl, alt: attributes.imageAlt || '', style: { maxWidth: '100%' } })
                      ),
                      el(TextControl, {
                        label: __('代替テキスト（alt）', 'cook-manual'),
                        value: attributes.imageAlt,
                        onChange: v => setAttributes({ imageAlt: v })
                      })
                    )
                  })
                ),
                attributes.mediaType === 'video' && el(TextControl, {
                  label: __('動画URL / oEmbed（YouTube等）', 'cook-manual'),
                  value: attributes.videoUrl,
                  onChange: v => setAttributes({ videoUrl: v })
                }),

                // ★ 編集画面の入力順を「警告 → ポイント」に変更
                el(TextControl, {
                  label: __('注意事項（警告）', 'cook-manual'),
                  value: attributes.noteWarning,
                  onChange: v => setAttributes({ noteWarning: v })
                }),
                el(TextControl, {
                  label: __('ポイント', 'cook-manual'),
                  value: attributes.noteImportant,
                  onChange: v => setAttributes({ noteImportant: v })
                }),

                el(NumberControl || TextControl, { // WPバージョン差吸収
                  label: __('タイマー秒数（任意）', 'cook-manual'),
                  value: attributes.timerSeconds,
                  onChange: v => setAttributes({ timerSeconds: parseInt(v || 0, 10) || 0 })
                })
              )
            ),

            // メディア プレビュー
            attributes.mediaType === 'image' && attributes.imageUrl && el('figure', { className: 'p-step__figure' },
              el('img', { src: attributes.imageUrl, alt: attributes.imageAlt || '' })
            ),
            attributes.mediaType === 'video' && attributes.videoUrl && el('div', { className: 'p-step__video' },
              el(Notice, { status: 'info', isDismissible: false }, __('プレビューは簡易表示です（保存後、フロントで埋め込み）', 'cook-manual')),
              el('div', {}, attributes.videoUrl)
            )
          ),

          // 右：内容（★順序：警告 → ポイント → 本文）
          el('div', { className: 'p-step__content' },

            // 警告（先頭）
            attributes.noteWarning && el('div', { className: 'p-step__notes' },
              el('div', { className: 'p-step__note p-step__note--warning', role: 'note', 'aria-label': '注意事項（警告）' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, __('注意事項（警告）', 'cook-manual')),
                el('p', { className: 'p-step__noteText' }, attributes.noteWarning)
              ),
              attributes.noteImportant && el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': 'ポイント' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, __('ポイント', 'cook-manual')),
                el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
              )
            ),

            // ポイントのみ（警告が無い場合でも順番を保つ）
            !attributes.noteWarning && attributes.noteImportant && el('div', { className: 'p-step__notes' },
              el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': 'ポイント' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, __('ポイント', 'cook-manual')),
                el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
              )
            ),

            // 手順本文（最後）
            el(RichText, {
              tagName: 'div',
              className: 'p-step__body',
              placeholder: __('本文（作業内容）を入力…', 'cook-manual'),
              value: attributes.body,
              onChange: v => setAttributes({ body: v })
            }),

            // タイマーなどアクション
            el('div', { className: 'p-step__actions' },
              (attributes.timerSeconds > 0) && el('span', {}, '⏱ ', attributes.timerSeconds, __('秒タイマー', 'cook-manual'))
            )
          )
        )
      );
    },

    save: function( props ) {
      const { attributes } = props;
      return el('li', { className: 'p-step', tabindex: '-1' },
        el('h2', { className: 'p-step__title' },
          el('span', { className: 'p-step__num' }, ''), // 連番はフロントJSで自動付与
          attributes.stepTitle || ''
        ),
        el('div', { className: 'p-step__inner' },

          // 左：メディア
          el('div', { className: 'p-step__media' },
            attributes.mediaType === 'image' && attributes.imageUrl && el('figure', { className: 'p-step__figure' },
              el('img', { src: attributes.imageUrl, alt: attributes.imageAlt || '' })
            ),
            attributes.mediaType === 'video' && attributes.videoUrl && el('div', { className: 'p-step__video' },
              el('iframe', {
                src: attributes.videoUrl,
                loading: 'lazy',
                allowFullScreen: true,
                title: attributes.stepTitle || 'manual-video'
              })
            )
          ),

          // 右：内容（★順序：警告 → ポイント → 本文）
          el('div', { className: 'p-step__content' },

            // 警告（先頭）
            attributes.noteWarning && el('div', { className: 'p-step__notes' },
              el('div', { className: 'p-step__note p-step__note--warning', role: 'note', 'aria-label': '注意事項（警告）' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '注意事項（警告）'),
                el('p', { className: 'p-step__noteText' }, attributes.noteWarning)
              ),
              attributes.noteImportant && el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': 'ポイント' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, 'ポイント'),
                el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
              )
            ),

            // ポイントのみ（警告が無い場合でも順番を保つ）
            !attributes.noteWarning && attributes.noteImportant && el('div', { className: 'p-step__notes' },
              el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': 'ポイント' },
                el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, 'ポイント'),
                el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
              )
            ),

            // 手順本文（最後）
            el(RichText.Content, { tagName: 'div', className: 'p-step__body', value: attributes.body || '' }),

            // タイマーなどアクション
            el('div', { className: 'p-step__actions' },
              (attributes.timerSeconds > 0) && el('button', {
                type: 'button',
                className: 'c-btn c-btn--timer js-step-timer',
                'data-seconds': attributes.timerSeconds,
                'aria-label': 'タイマー' + attributes.timerSeconds + '秒を開始'
              }, '⏱ タイマー（' + attributes.timerSeconds + '秒）'),
              el('span', { className: 'p-step__countdown', 'aria-live': 'polite' })
            )
          )
        )
      );
    },

    // ---- 旧マークアップ互換（自動移行） ----
    deprecated: [
      // dep0: 直前世代（ラベル「重要」＆並び：警告→重要→本文）
      {
        attributes: {
          stepTitle: { type: 'string', default: '' },
          mediaType: { type: 'string', default: 'image' },
          imageUrl:  { type: 'string', default: '' },
          imageAlt:  { type: 'string', default: '' },
          videoUrl:  { type: 'string', default: '' },
          body:      { type: 'string', source: 'html', selector: '.p-step__body' },
          noteImportant: { type: 'string', default: '' },
          noteWarning:   { type: 'string', default: '' },
          timerSeconds:  { type: 'number', default: 0 },
        },
        save: function( props ) {
          const { attributes } = props;
          return el('li', { className: 'p-step', tabindex: '-1' },
            el('h2', { className: 'p-step__title' },
              el('span', { className: 'p-step__num' }, ''),
              attributes.stepTitle || ''
            ),
            el('div', { className: 'p-step__inner' },
              el('div', { className: 'p-step__media' },
                attributes.mediaType === 'image' && attributes.imageUrl && el('figure', { className: 'p-step__figure' },
                  el('img', { src: attributes.imageUrl, alt: attributes.imageAlt || '' })
                ),
                attributes.mediaType === 'video' && attributes.videoUrl && el('div', { className: 'p-step__video' },
                  el('iframe', { src: attributes.videoUrl, loading: 'lazy', allowFullScreen: true, title: attributes.stepTitle || 'manual-video' })
                )
              ),
              el('div', { className: 'p-step__content' },
                // 旧ラベル：重要
                attributes.noteWarning && el('div', { className: 'p-step__notes' },
                  el('div', { className: 'p-step__note p-step__note--warning', role: 'note', 'aria-label': '警告' },
                    el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '警告'),
                    el('p', { className: 'p-step__noteText' }, attributes.noteWarning)
                  ),
                  attributes.noteImportant && el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': '重要' },
                    el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '重要'),
                    el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
                  )
                ),
                !attributes.noteWarning && attributes.noteImportant && el('div', { className: 'p-step__notes' },
                  el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': '重要' },
                    el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '重要'),
                    el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
                  )
                ),
                el(RichText.Content, { tagName: 'div', className: 'p-step__body', value: attributes.body || '' }),
                el('div', { className: 'p-step__actions' },
                  (attributes.timerSeconds > 0) && el('button', {
                    type: 'button',
                    className: 'c-btn c-btn--timer js-step-timer',
                    'data-seconds': attributes.timerSeconds,
                    'aria-label': 'タイマー' + attributes.timerSeconds + '秒を開始'
                  }, '⏱ タイマー（' + attributes.timerSeconds + '秒）'),
                  el('span', { className: 'p-step__countdown', 'aria-live': 'polite' })
                )
              )
            )
          );
        },
        migrate: function( attributes ) { return attributes; }
      },

      // dep1: さらに旧世代（.p-step__body 内に div を持つ構造）
      {
        attributes: {
          stepTitle: { type: 'string', default: '' },
          mediaType: { type: 'string', default: 'image' },
          imageUrl:  { type: 'string', default: '' },
          imageAlt:  { type: 'string', default: '' },
          videoUrl:  { type: 'string', default: '' },
          body:      { type: 'string', source: 'html', selector: '.p-step__body div' },
          noteImportant: { type: 'string', default: '' },
          noteWarning:   { type: 'string', default: '' },
          timerSeconds:  { type: 'number', default: 0 },
        },
        save: function( props ) {
          const { attributes } = props;
          return el('li', { className: 'p-step', tabindex: '-1' },
            el('h2', { className: 'p-step__title' },
              el('span', { className: 'p-step__num' }, ''),
              attributes.stepTitle || ''
            ),
            el('div', { className: 'p-step__inner' },
              el('div', { className: 'p-step__media' },
                attributes.mediaType === 'image' && attributes.imageUrl && el('figure', { className: 'p-step__figure' },
                  el('img', { src: attributes.imageUrl, alt: attributes.imageAlt || '' })
                ),
                attributes.mediaType === 'video' && attributes.videoUrl && el('div', { className: 'p-step__video' },
                  el('iframe', { src: attributes.videoUrl, loading: 'lazy', allowFullScreen: true, title: attributes.stepTitle || 'manual-video' })
                )
              ),
              el('div', { className: 'p-step__content' },
                el('div', { className: 'p-step__body' },
                  el(RichText.Content, { tagName: 'div', value: attributes.body || '' })
                ),
                (attributes.noteImportant || attributes.noteWarning) && el('div', { className: 'p-step__notes' },
                  attributes.noteImportant && el('div', { className: 'p-step__note p-step__note--important', role: 'note', 'aria-label': '重要' },
                    el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '重要'),
                    el('p', { className: 'p-step__noteText' }, attributes.noteImportant)
                  ),
                  attributes.noteWarning && el('div', { className: 'p-step__note p-step__note--warning', role: 'note', 'aria-label': '警告' },
                    el('strong', { className: 'p-step__noteLabel', 'aria-hidden': 'true' }, '警告'),
                    el('p', { className: 'p-step__noteText' }, attributes.noteWarning)
                  )
                ),
                el('div', { className: 'p-step__actions' },
                  (attributes.timerSeconds > 0) && el('button', {
                    type: 'button',
                    className: 'c-btn c-btn--timer js-step-timer',
                    'data-seconds': attributes.timerSeconds,
                    'aria-label': 'タイマー' + attributes.timerSeconds + '秒を開始'
                  }, '⏱ タイマー（' + attributes.timerSeconds + '秒）'),
                  el('span', { className: 'p-step__countdown', 'aria-live': 'polite' })
                )
              )
            )
          );
        },
        migrate: function( attributes ) { return attributes; }
      }
    ]
  });

  // ---------- 親ブロック：手順全体 ----------
  registerBlockType('officeueda/manual-steps', {
    apiVersion: 2,
    title: __('調理マニュアル：手順リスト', 'cook-manual'),
    icon: 'editor-ol',
    category: 'layout',
    supports: { html: false },
    edit: function( props ) {
      const blockProps = useBlockProps({ className: 'p-steps' });
      const ALLOWED = ['officeueda/manual-step'];
      return el('ol', blockProps,
        el(InnerBlocks, {
          allowedBlocks: ALLOWED,
          renderAppender: InnerBlocks.ButtonBlockAppender
        })
      );
    },
    save: function() {
      return el('ol', wp.blockEditor ? wp.blockEditor.useBlockProps.save({ className: 'p-steps' }) : { className: 'p-steps' },
        el(InnerBlocks.Content)
      );
    }
  });

} )( window.wp );
