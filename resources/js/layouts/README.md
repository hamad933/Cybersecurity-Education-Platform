# CEP Workspace Layout Contract

`CepWorkspaceLayout.vue` is the Wave-1 shared integration surface for domain pages.

## Props

- `activeDestination`: one of `today | knowledge | simulation | progress | system`.
- `temporaryWorkspaceOpen`: optional boolean; defaults to `false`.
- `temporaryWorkspaceLabel`: optional accessible label for the temporary bottom workspace.

## Slots

- `primaryNavigation`: optional primary-area navigation inside the active global workspace.
- `top`: current tools and workflow actions only.
- `left`: structure and navigation only.
- default slot: the CENTER primary work surface.
- `right`: unique contextual information only.
- `bottom`: temporary deep work; it is not rendered until `temporaryWorkspaceOpen` is true.

The layout emits `closeTemporaryWorkspace` when the shared temporary-workspace close control is used.

Global destination labels and stable hrefs are defined once in
`resources/js/components/cep/navigation.ts`. Domain pages must not add legacy
`/vs001`, `/vs002`, or `/vs003` entries to the global navigation.
