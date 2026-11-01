@props(['report'])

<p {{ $attributes }}>
    @if(($report['scoring_version'] ?? null) === \App\Support\Seo\ReportScores::VERSION)
        Weighted score: subchecks earn 100% for a pass, 25% for a warning and 0% for an error. Each check group is averaged, then weighted by importance: very important 8, important 4, low importance 2, nice to have 1. Category weights are Meta data 30%, Page quality 25%, Others 15%, Link structure 15% and Server configuration 15%. Unverified checks and unscored probes are excluded; unavailable categories are omitted and the remaining weights are normalized. External metrics do not affect the score.
    @else
        This saved report uses the original scoring method: equally weighted subchecks with 50% credit for warnings. Run a new check to use the weighted scoring model.
    @endif
    This is our single-page assessment, not a ranking prediction. Other SEO tools use different checks and scoring formulas.
</p>
