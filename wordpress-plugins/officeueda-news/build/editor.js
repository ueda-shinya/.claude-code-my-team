(function (wp) {
  const { registerBlockType } = wp.blocks;
  const { useBlockProps, InspectorControls } = wp.blockEditor || wp.editor;
  const {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
    __experimentalNumberControl: NumberControl,
  } = wp.components;

  registerBlockType("officeueda/news", {
    edit: (props) => {
      const { attributes, setAttributes } = props;
      const bp = useBlockProps();
      return wp.element.createElement(
        "div",
        bp,
        wp.element.createElement(
          InspectorControls,
          {},
          wp.element.createElement(
            PanelBody,
            { title: "表示設定", initialOpen: true },
            wp.element.createElement(SelectControl, {
              label: "モード",
              value: attributes.mode,
              options: [
                { label: "最新N件", value: "latest" },
                { label: "一覧", value: "archive" },
                { label: "単体", value: "entry" },
              ],
              onChange: (v) => setAttributes({ mode: v }),
            }),
            attributes.mode === "latest" && [
              wp.element.createElement(NumberControl, {
                key: "limit",
                label: "件数",
                value: attributes.limit,
                onChange: (v) =>
                  setAttributes({ limit: parseInt(v || 0, 10) || 1 }),
              }),
              wp.element.createElement(TextControl, {
                key: "cat",
                label: "カテゴリスラッグ",
                value: attributes.cat,
                onChange: (v) => setAttributes({ cat: v }),
              }),
              wp.element.createElement(ToggleControl, {
                key: "pin",
                label: "先頭固定を優先",
                checked: !!attributes.pinnedFirst,
                onChange: (v) => setAttributes({ pinnedFirst: v }),
              }),
            ],
            attributes.mode === "archive" && [
              wp.element.createElement(NumberControl, {
                key: "pp",
                label: "ページあたり件数",
                value: attributes.perPage,
                onChange: (v) =>
                  setAttributes({ perPage: parseInt(v || 0, 10) || 1 }),
              }),
              wp.element.createElement(TextControl, {
                key: "cat2",
                label: "カテゴリスラッグ",
                value: attributes.cat,
                onChange: (v) => setAttributes({ cat: v }),
              }),
              wp.element.createElement(TextControl, {
                key: "qs",
                label: "ページ送りクエリ名",
                value: attributes.pageQs,
                onChange: (v) =>
                  setAttributes({
                    pageQs: (v || "npage").replace(/[^a-z0-9_]/gi, ""),
                  }),
              }),
            ],
            attributes.mode === "entry" && [
              wp.element.createElement(NumberControl, {
                key: "pid",
                label: "投稿ID",
                value: attributes.postId,
                onChange: (v) =>
                  setAttributes({ postId: parseInt(v || 0, 10) || 0 }),
              }),
              wp.element.createElement(TextControl, {
                key: "slug",
                label: "スラッグ（IDとどちらか）",
                value: attributes.slug,
                onChange: (v) => setAttributes({ slug: v }),
              }),
            ],
            wp.element.createElement(TextControl, {
              key: "tpl",
              label: "テンプレ名（parts/news/*.php）",
              value: attributes.template,
              onChange: (v) =>
                setAttributes({
                  template: (v || "latest").replace(/[^a-z0-9\-_]/gi, ""),
                }),
            })
          )
        ),
        wp.element.createElement(
          "div",
          { className: "ou-news-block-preview" },
          wp.element.createElement(
            "p",
            null,
            "プレビューは公開側に準拠（動的レンダー）。"
          ),
          wp.element.createElement(
            "ul",
            null,
            wp.element.createElement("li", null, "モード: ", attributes.mode),
            wp.element.createElement(
              "li",
              null,
              "テンプレート: ",
              attributes.template
            ),
            attributes.mode === "latest" &&
              wp.element.createElement(
                "li",
                null,
                `件数:${attributes.limit} / カテゴリ:${
                  attributes.cat
                } / 先頭固定:${attributes.pinnedFirst ? "ON" : "OFF"}`
              ),
            attributes.mode === "archive" &&
              wp.element.createElement(
                "li",
                null,
                `1ページ:${attributes.perPage} / カテゴリ:${attributes.cat} / クエリ:${attributes.pageQs}`
              ),
            attributes.mode === "entry" &&
              wp.element.createElement(
                "li",
                null,
                `ID:${attributes.postId} / スラッグ:${attributes.slug}`
              )
          )
        )
      );
    },
    save: () => null,
  });
})(window.wp);
