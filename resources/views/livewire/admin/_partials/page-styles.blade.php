<style>
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-block-end: var(--space-6);
    gap: var(--space-4);
}
.page-title {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}
.filters-bar {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    flex-wrap: wrap;
    margin-block-end: var(--space-4);
}
.card {
    background: var(--surface-card);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    padding: var(--card-padding);
    box-shadow: var(--card-shadow);
}
.empty-state {
    text-align: center;
    color: var(--text-secondary);
    padding: var(--space-8);
    font-style: italic;
}
.empty-state-block {
    background: var(--surface-card);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    padding: var(--space-12);
    text-align: center;
    color: var(--text-secondary);
}
</style>
