(function (blocks, element, components, i18n, blockEditor, data) {
	const { registerBlockType } = blocks;
	const { createElement: el, useState, useEffect } = element;
	const {
		PanelBody,
		TextControl,
		BaseControl,
		Button,
		Notice,
	} = components;
	const { InspectorControls } = blockEditor || wp.editor;
	const { __ } = i18n;
	const { useSelect } = data;

	const SETTINGS = window.OUPB_BLOCK_SETTINGS || {};
	const I18N = SETTINGS.i18n || {};

	const t = (key, fallback) => {
		if (I18N[key]) {
			return I18N[key];
		}
		return __(fallback || key, 'ou-parameter-protect');
	};

	registerBlockType('oupb/protected-page', {
		title: t('title', 'パラメータ保護ページ'),
		description: t(
			'description',
			'このブロックが挿入されたページは、URLパラメータによって保護されます。'
		),
		icon: 'lock',
		category: 'widgets',
		supports: {
			html: false,
		},

		attributes: {
			secret: {
				type: 'string',
				default: '',
			},
			expiry: {
				type: 'string',
				default: '',
			},
		},

		edit: function (props) {
			const { attributes, setAttributes, clientId } = props;
			const { secret, expiry } = attributes;

			const [copyState, setCopyState] = useState(null); // 'success' | 'error' | null

			// エディタの状態から、現在の投稿情報を取得
			const selectData = useSelect(
				(select) => {
					const editor = select('core/editor');
					if (!editor) {
						return {
							permalink: '',
							status: '',
							blockCount: 0,
						};
					}
					const post = editor.getCurrentPost() || {};
					const permalink = editor.getPermalink
						? editor.getPermalink()
						: '';
					const status = post.status || '';

					// ページ内の保護ブロック数
					const be = select('core/block-editor') || select('core/editor');
					let blockCount = 0;
					if (be && be.getBlocks) {
						const blocks = be.getBlocks();
						if (Array.isArray(blocks)) {
							blockCount = countProtectedBlocks(blocks);
						}
					}

					return {
						permalink: permalink || '',
						status,
						blockCount,
					};
				},
				[clientId]
			);

			const { permalink, status, blockCount } = selectData;

			// 公開済みかどうか
			const isPublished = status === 'publish';

			// パラメータ名
			const paramName = SETTINGS.paramName || 'id';

			// 招待URLプレビュー
			const inviteUrl = buildInviteUrl(permalink, paramName, secret);

			// URLコピー
			const handleCopy = () => {
				if (!inviteUrl) {
					setCopyState('error');
					return;
				}

				if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard
						.writeText(inviteUrl)
						.then(() => {
							setCopyState('success');
							setTimeout(() => setCopyState(null), 2000);
						})
						.catch(() => {
							setCopyState('error');
							setTimeout(() => setCopyState(null), 2000);
						});
				} else {
					// フォールバック（互換用）
					try {
						const temp = document.createElement('input');
						temp.style.position = 'absolute';
						temp.style.left = '-9999px';
						temp.value = inviteUrl;
						document.body.appendChild(temp);
						temp.select();
						document.execCommand('copy');
						document.body.removeChild(temp);
						setCopyState('success');
						setTimeout(() => setCopyState(null), 2000);
					} catch (e) {
						setCopyState('error');
						setTimeout(() => setCopyState(null), 2000);
					}
				}
			};

			// シークレット未入力警告（保存時はPHP側でもバリデーションするが、エディタでも分かるように）
			const isSecretEmpty = !secret || secret.trim() === '';

			return el(
				'div',
				{ className: 'c-oupb-block' },
				// インスペクタ（右サイドバー）
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: t('title', 'パラメータ保護ページ'),
							initialOpen: true,
						},
						el(TextControl, {
							label: t('labelSecret', 'シークレット値（必須）'),
							help: t(
								'secretHelp',
								'URLパラメータの値として使用される文字列です。URLを知っている人だけがページを閲覧できます。'
							),
							value: secret,
							onChange: (value) => setAttributes({ secret: value }),
							placeholder: 'abc123',
						}),
						el(TextControl, {
							label: t('labelExpiry', '有効期限（任意）'),
							help: t(
								'expiryHelp',
								'この日時を過ぎると、一般ユーザーはこのURLからアクセスできなくなります。空欄の場合は期限なしです。例: 2025-12-31 23:59'
							),
							value: expiry,
							onChange: (value) => setAttributes({ expiry: value }),
							placeholder: '2025-12-31 23:59',
						})
					),
					el(
						PanelBody,
						{
							title: t('labelPreview', '招待URLプレビュー'),
							initialOpen: true,
						},
						el(
							BaseControl,
							{ label: t('labelPreview', '招待URLプレビュー') },
							el(
								'div',
								{ className: 'c-oupb-block__url-preview' },
								inviteUrl
									? el(
											'code',
											{ style: { wordBreak: 'break-all' } },
											inviteUrl
									  )
									: el(
											'p',
											{ style: { margin: 0 } },
											isSecretEmpty
												? t(
														'secretRequired',
														'シークレット値を入力すると招待URLが表示されます。'
												  )
												: t(
														'noPermalink',
														'パーマリンク情報がまだ取得できていません。下書き保存または公開後にご確認ください。'
												  )
									  )
							),
							el(
								'div',
								{ style: { marginTop: '8px' } },
								el(
									Button,
									{
										variant: 'secondary',
										onClick: handleCopy,
										disabled: !inviteUrl,
									},
									t('copyButton', 'URLをコピー')
								),
								copyState === 'success' &&
									el(
										'p',
										{
											style: {
												color: 'green',
												margin: '4px 0 0',
												fontSize: '12px',
											},
										},
										t('copySuccess', 'URLをコピーしました。')
									),
								copyState === 'error' &&
									el(
										'p',
										{
											style: {
												color: 'red',
												margin: '4px 0 0',
												fontSize: '12px',
											},
										},
										t('copyFailure', 'URLのコピーに失敗しました。')
									)
							),
							!isPublished &&
								el(
									'p',
									{
										style: {
											marginTop: '8px',
											fontSize: '12px',
											color: '#666',
										},
									},
									t(
										'noticeUnpublished',
										'まだ公開されていないため、URLは変更される可能性があります。公開後にURLを確認してください。'
									)
								)
						)
					)
				),

				// ブロック本体（エディタ上に見える部分）
				el(
					'div',
					{ className: 'c-oupb-block__body' },
					blockCount > 1 &&
						el(
							Notice,
							{
								status: 'warning',
								isDismissible: false,
								className: 'c-oupb-block__notice-multiple',
							},
							t(
								'noticeMultiple',
								'このページには複数の保護ブロックがあります。最初の1つだけが有効になります。'
							)
						),
					isSecretEmpty &&
						el(
							Notice,
							{
								status: 'error',
								isDismissible: false,
								className: 'c-oupb-block__notice-secret',
							},
							t(
								'secretRequired',
								'シークレット値は必須です。未入力のまま公開すると保護が正しく動作しない可能性があります。'
							)
						),
					el(
						'p',
						{ className: 'c-oupb-block__label' },
						t(
							'description',
							'このブロックが挿入されたページは、URLパラメータによって保護されます。'
						)
					),
					el(
						'ul',
						{ className: 'c-oupb-block__summary' },
						el(
							'li',
							null,
							t(
								'summarySecret',
								'・シークレット値＋パラメータ名を知っている人だけが閲覧できます。'
							)
						),
						el(
							'li',
							null,
							t(
								'summaryExpiry',
								'・有効期限を設定すると、その日時を過ぎた一般ユーザーはアクセスできません。'
							)
						),
						el(
							'li',
							null,
							t(
								'summaryScope',
								'・このブロックが挿入されたページ全体が保護対象になります。'
							)
						)
					)
				)
			);
		},

		save: function () {
			// レンダーは PHP 側（render_callback）で行うので、ここでは何も出力しない
			return null;
		},
	});

	/**
	 * ページ内の「パラメータ保護ブロック」の数を数える
	 */
	function countProtectedBlocks(blocks) {
		let count = 0;

		blocks.forEach((block) => {
			if (!block) return;
			if (block.name === 'oupb/protected-page') {
				count += 1;
			}
			if (Array.isArray(block.innerBlocks) && block.innerBlocks.length > 0) {
				count += countProtectedBlocks(block.innerBlocks);
			}
		});

		return count;
	}

	/**
	 * 招待URLを生成
	 *
	 * @param {string} permalink 基本パーマリンク
	 * @param {string} paramName パラメータ名
	 * @param {string} secret    シークレット値
	 * @returns {string}
	 */
	function buildInviteUrl(permalink, paramName, secret) {
		if (!permalink || !secret || !secret.trim()) {
			return '';
		}

		try {
			const url = new window.URL(permalink);
			const searchParams = url.searchParams;
			searchParams.set(paramName, secret.trim());
			url.search = searchParams.toString();
			return url.toString();
		} catch (e) {
			// URLコンストラクタが使えない古い環境など
			const sep = permalink.indexOf('?') === -1 ? '?' : '&';
			return permalink + sep + encodeURIComponent(paramName) + '=' + encodeURIComponent(secret.trim());
		}
	}
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.components,
	window.wp.i18n,
	window.wp.blockEditor || window.wp.editor,
	window.wp.data
);
