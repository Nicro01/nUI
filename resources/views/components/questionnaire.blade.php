@props([
    'questions' => [],
    'value' => [],
    'name' => null,
    'label' => 'Questionário',
    'title' => null,
    'description' => null,
    'mode' => 'stepped',
    'variant' => 'default',
    'progress' => true,
    'review' => true,
    'scored' => false,
    'readonly' => false,
    'backLabel' => 'Voltar',
    'nextLabel' => 'Continuar',
    'skipLabel' => 'Pular',
    'reviewLabel' => 'Revisar respostas',
    'submitLabel' => 'Enviar respostas',
    'restartLabel' => 'Responder novamente',
    'completedTitle' => 'Respostas registradas',
    'completedDescription' => 'Suas informações foram enviadas com sucesso.',
])

@php
    $id = $attributes->get('id') ?? 'nui-quest-'.Str::random(6);
    $locked = $readonly || (bool) $attributes->get('disabled');
    $mode = in_array($mode, ['stepped', 'stacked'], true) ? $mode : 'stepped';
    $variant = in_array($variant, ['default', 'outline', 'elevated', 'subtle', 'plain'], true) ? $variant : 'default';

    $allowedTypes = ['single', 'multiple', 'boolean', 'scale', 'rating', 'text', 'textarea', 'number', 'range', 'select', 'date'];
    $seenIds = [];
    $normalizedQuestions = [];

    foreach ($questions as $index => $question) {
        $qId = (string) ($question['id'] ?? $question['name'] ?? (is_string($index) ? $index : ''));
        if ($qId === '' || isset($seenIds[$qId])) {
            throw new InvalidArgumentException('Questionnaire: cada pergunta precisa de um id único.');
        }
        $seenIds[$qId] = true;

        $type = strtolower((string) ($question['type'] ?? 'single'));
        if (! in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException("Questionnaire: tipo de pergunta inválido ({$type}).");
        }

        $rawOptions = $question['options'] ?? [];
        if ($type === 'boolean' && empty($rawOptions)) {
            $rawOptions = ['sim' => 'Sim', 'nao' => 'Não'];
        }

        $options = collect($rawOptions)->map(fn ($opt, $optKey) => is_array($opt)
            ? [
                'value' => (string) ($opt['value'] ?? $optKey),
                'label' => (string) ($opt['label'] ?? $opt['value'] ?? $optKey),
                'description' => isset($opt['description']) ? (string) $opt['description'] : null,
                'points' => isset($opt['points']) ? (float) $opt['points'] : null,
                'correct' => (bool) ($opt['correct'] ?? false),
            ]
            : [
                'value' => (string) (array_is_list($rawOptions) ? $opt : $optKey),
                'label' => (string) $opt,
                'description' => null,
                'points' => null,
                'correct' => false,
            ]
        )->values()->all();

        $min = isset($question['min']) ? (float) $question['min'] : match ($type) {
            'scale' => 1.0,
            'rating' => 1.0,
            'range' => 0.0,
            default => null,
        };
        $max = isset($question['max']) ? (float) $question['max'] : match ($type) {
            'scale' => 5.0,
            'rating' => 5.0,
            'range' => 100.0,
            default => null,
        };
        $step = isset($question['step']) ? (float) $question['step'] : 1.0;

        $scaleValues = [];
        if ($type === 'scale') {
            $sMin = (int) ($min ?? 1);
            $sMax = (int) ($max ?? 5);
            if ($sMax < $sMin) {
                [$sMin, $sMax] = [$sMax, $sMin];
            }
            $sMax = min($sMin + 10, $sMax);
            $scaleValues = range($sMin, $sMax);
        }

        $when = null;
        if (isset($question['when']) && is_array($question['when'])) {
            $whenField = (string) ($question['when']['field'] ?? $question['when']['question'] ?? '');
            if ($whenField !== '') {
                $when = [
                    'field' => $whenField,
                    'equals' => $question['when']['equals'] ?? null,
                    'in' => isset($question['when']['in']) ? array_map('strval', (array) $question['when']['in']) : null,
                    'not' => $question['when']['not'] ?? null,
                    'filled' => isset($question['when']['filled']) ? (bool) $question['when']['filled'] : null,
                ];
            }
        }

        $correct = $question['correct'] ?? null;
        if ($correct === null && in_array($type, ['single', 'boolean', 'select'], true)) {
            $correctOpt = collect($options)->firstWhere('correct', true);
            if ($correctOpt) {
                $correct = $correctOpt['value'];
            }
        } elseif ($correct === null && $type === 'multiple') {
            $correctOpts = collect($options)->where('correct', true)->pluck('value')->values()->all();
            if (count($correctOpts)) {
                $correct = $correctOpts;
            }
        }

        $normalizedQuestions[] = [
            'id' => $qId,
            'type' => $type,
            'title' => (string) ($question['title'] ?? $question['label'] ?? Str::headline($qId)),
            'description' => isset($question['description']) ? (string) $question['description'] : null,
            'hint' => isset($question['hint']) ? (string) $question['hint'] : null,
            'section' => isset($question['section']) ? (string) $question['section'] : null,
            'required' => (bool) ($question['required'] ?? false),
            'placeholder' => (string) ($question['placeholder'] ?? ($type === 'select' ? 'Selecione…' : '')),
            'searchable' => (bool) ($question['searchable'] ?? false),
            'options' => $options,
            'scaleValues' => $scaleValues,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'minLabel' => isset($question['minLabel']) ? (string) $question['minLabel'] : null,
            'maxLabel' => isset($question['maxLabel']) ? (string) $question['maxLabel'] : null,
            'prefix' => (string) ($question['prefix'] ?? ''),
            'suffix' => (string) ($question['suffix'] ?? ''),
            'rows' => max(2, (int) ($question['rows'] ?? 3)),
            'default' => $question['default'] ?? null,
            'when' => $when,
            'points' => isset($question['points']) ? (float) $question['points'] : ($correct !== null ? 1.0 : 0.0),
            'correct' => $correct,
            'explanation' => isset($question['explanation']) ? (string) $question['explanation'] : null,
        ];
    }

    $provided = is_array($value) ? $value : [];
    if ($name && is_array(old($name))) {
        $provided = array_replace($provided, old($name));
    }

    $initialAnswers = [];
    foreach ($normalizedQuestions as $q) {
        $qId = $q['id'];
        $raw = array_key_exists($qId, $provided) ? $provided[$qId] : $q['default'];

        $initialAnswers[$qId] = match ($q['type']) {
            'multiple' => array_values(array_map('strval', (array) ($raw ?? []))),
            'range' => (float) ($raw ?? $q['min'] ?? 0),
            'rating' => $raw !== null && $raw !== '' ? (int) $raw : 0,
            'scale' => $raw !== null && $raw !== '' ? (string) $raw : '',
            'number' => $raw !== null && $raw !== '' ? $raw : '',
            default => $raw !== null ? (string) $raw : '',
        };
    }

    $hasHeader = filled($title) || filled($description) || isset($header);
@endphp

{{--
    Interactive Questionnaire composed with nUI primitives:
    Card, Progress, Fieldset, Radio, Checkbox, Rating, Input, Textarea, Number, Range, Select, Date, Alert, Tag, Button.
    Supports stepped (one question at a time + optional review screen) and stacked (all active questions at once) flows,
    conditional branching (`when` rules), required validation, optional scoring (`scored`), plain form submission and x-model / wire:model.
--}}
<div {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['id', 'disabled'])->class([
        'nui-questionnaire flex w-full min-w-0 flex-col text-base-content',
        match ($variant) {
            'plain' => '',
            'outline' => 'rounded-nui bg-transparent border border-base-content/15 border-b-[3px] border-b-base-content/20 p-6',
            'elevated' => 'rounded-nui bg-base-100 shadow-[0_1px_2px_oklch(0%_0_0/0.06),0_18px_40px_-18px_oklch(0%_0_0/0.3)] p-6',
            'subtle' => 'rounded-nui bg-base-200 p-6',
            default => 'rounded-nui bg-base-100 shadow-nui border-[length:var(--nui-border-width)] border-base-300 border-b-[3px] border-b-base-content/20 p-6',
        },
    ]) }}
    id="{{ $id }}" role="region"
    @if ($title) aria-labelledby="{{ $id }}-title" @else aria-label="{{ $label }}" @endif
    data-nui-questionnaire data-mode="{{ $mode }}" data-variant="{{ $variant }}"
    x-data="{
        questions: @js($normalizedQuestions),
        answers: @js($initialAnswers),
        initialDefaults: @js($initialAnswers),
        mode: @js($mode),
        reviewEnabled: @js((bool) $review),
        scored: @js((bool) $scored),
        locked: @js($locked),
        currentIndex: 0,
        reviewing: false,
        submitted: false,
        errors: {},
        notice: '',
        init() {
            this.$watch('answers', () => {
                const active = this.activeQuestions();
                if (this.currentIndex >= active.length && active.length > 0) {
                    this.currentIndex = active.length - 1;
                }
            }, { deep: true });
        },
        findQuestion(id) {
            return this.questions.find(q => q.id === id) || null;
        },
        isVisible(q) {
            if (!q || !q.when) return true;
            const rule = q.when;
            const target = this.answers[rule.field];
            if (rule.filled !== null && rule.filled !== undefined) {
                const has = Array.isArray(target) ? target.length > 0 : (target !== null && target !== undefined && String(target).trim() !== '' && !(this.findQuestion(rule.field)?.type === 'rating' && Number(target) === 0));
                if (Boolean(rule.filled) !== has) return false;
            }
            if (rule.equals !== null && rule.equals !== undefined) {
                if (Array.isArray(target)) {
                    if (!target.map(String).includes(String(rule.equals))) return false;
                } else if (String(target ?? '') !== String(rule.equals)) {
                    return false;
                }
            }
            if (Array.isArray(rule.in) && rule.in.length > 0) {
                const allowed = rule.in.map(String);
                if (Array.isArray(target)) {
                    if (!target.some(v => allowed.includes(String(v)))) return false;
                } else if (!allowed.includes(String(target ?? ''))) {
                    return false;
                }
            }
            if (rule.not !== null && rule.not !== undefined) {
                if (Array.isArray(target)) {
                    if (target.map(String).includes(String(rule.not))) return false;
                } else if (String(target ?? '') === String(rule.not)) {
                    return false;
                }
            }
            return true;
        },
        activeQuestions() {
            return this.questions.filter(q => this.isVisible(q));
        },
        activeIndexOf(id) {
            return this.activeQuestions().findIndex(q => q.id === id);
        },
        currentQuestion() {
            const active = this.activeQuestions();
            return active[this.currentIndex] || active[0] || null;
        },
        isAnswered(q) {
            if (!q) return false;
            const val = this.answers[q.id];
            if (q.type === 'multiple') return Array.isArray(val) && val.length > 0;
            if (q.type === 'rating') return Number(val) > 0;
            if (q.type === 'range') return val !== null && val !== undefined && val !== '';
            return val !== null && val !== undefined && String(val).trim() !== '';
        },
        answeredCount() {
            return this.activeQuestions().filter(q => this.isAnswered(q)).length;
        },
        progressPercent() {
            const active = this.activeQuestions();
            if (!active.length) return 100;
            if (this.submitted) return 100;
            if (this.mode === 'stacked') {
                return Math.round((this.answeredCount() / active.length) * 100);
            }
            if (this.reviewing) return 100;
            return Math.round(((this.currentIndex + 1) / active.length) * 100);
        },
        validateQuestion(q) {
            if (!q || !this.isVisible(q)) return true;
            if (q.required && !this.isAnswered(q)) {
                this.errors = { ...this.errors, [q.id]: 'Responda a esta pergunta antes de continuar.' };
                this.notice = 'A pergunta ' + q.title + ' é obrigatória.';
                return false;
            }
            if (q.type === 'number' && this.isAnswered(q)) {
                const num = Number(this.answers[q.id]);
                if (q.min !== null && num < q.min) {
                    this.errors = { ...this.errors, [q.id]: 'O valor mínimo é ' + q.min + '.' };
                    return false;
                }
                if (q.max !== null && num > q.max) {
                    this.errors = { ...this.errors, [q.id]: 'O valor máximo é ' + q.max + '.' };
                    return false;
                }
            }
            const nextErrors = { ...this.errors };
            delete nextErrors[q.id];
            this.errors = nextErrors;
            return true;
        },
        clearError(id) {
            if (!this.errors[id]) return;
            const nextErrors = { ...this.errors };
            delete nextErrors[id];
            this.errors = nextErrors;
        },
        validateAll() {
            let valid = true;
            let firstInvalidId = null;
            for (const q of this.activeQuestions()) {
                if (!this.validateQuestion(q) && valid) {
                    valid = false;
                    firstInvalidId = q.id;
                }
            }
            if (!valid && firstInvalidId && this.mode === 'stepped') {
                this.reviewing = false;
                const idx = this.activeIndexOf(firstInvalidId);
                if (idx !== -1) this.currentIndex = idx;
            }
            return valid;
        },
        recordChange(qOrId) {
            const id = typeof qOrId === 'string' ? qOrId : qOrId?.id;
            if (!id) return;
            this.clearError(id);
            this.$dispatch('questionnaire-answer', {
                questionId: id,
                value: this.answers[id],
                answers: JSON.parse(JSON.stringify(this.answers)),
            });
        },
        setAnswer(id, val) {
            this.answers[id] = String(val);
            this.recordChange(id);
        },
        next() {
            const current = this.currentQuestion();
            if (!this.validateQuestion(current)) return;
            const active = this.activeQuestions();
            if (this.currentIndex < active.length - 1) {
                this.currentIndex++;
                this.notice = 'Pergunta ' + (this.currentIndex + 1) + ' de ' + active.length + ': ' + (this.currentQuestion()?.title || '');
                this.$dispatch('questionnaire-step', { index: this.currentIndex, questionId: this.currentQuestion()?.id });
            } else if (this.reviewEnabled) {
                this.reviewing = true;
                this.notice = @js($reviewLabel);
            } else {
                this.submit();
            }
        },
        skip() {
            const current = this.currentQuestion();
            if (!current || current.required) return;
            this.clearError(current.id);
            const active = this.activeQuestions();
            if (this.currentIndex < active.length - 1) {
                this.currentIndex++;
                this.$dispatch('questionnaire-step', { index: this.currentIndex, questionId: this.currentQuestion()?.id });
            } else if (this.reviewEnabled) {
                this.reviewing = true;
            } else {
                this.submit();
            }
        },
        back() {
            if (this.reviewing) {
                this.reviewing = false;
                return;
            }
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.$dispatch('questionnaire-step', { index: this.currentIndex, questionId: this.currentQuestion()?.id });
            }
        },
        jumpTo(id) {
            const idx = this.activeIndexOf(id);
            if (idx === -1) return;
            this.reviewing = false;
            this.currentIndex = idx;
            this.$dispatch('questionnaire-step', { index: idx, questionId: id });
        },
        displayAnswer(q) {
            if (!q || !this.isAnswered(q)) return 'Não respondida';
            const val = this.answers[q.id];
            if (q.type === 'multiple' && Array.isArray(val)) {
                return val.map(v => q.options.find(o => o.value === String(v))?.label ?? v).join(', ');
            }
            if (['single', 'boolean', 'select'].includes(q.type)) {
                return q.options.find(o => o.value === String(val))?.label ?? String(val);
            }
            if (q.type === 'rating') {
                return val + ' de 5 estrelas';
            }
            if (q.type === 'scale') {
                return String(val) + (q.max ? ' / ' + q.max : '');
            }
            if (q.type === 'range' || q.type === 'number') {
                return (q.prefix || '') + String(val) + (q.suffix || '');
            }
            if (q.type === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(String(val))) {
                const [y, m, d] = String(val).split('-').map(Number);
                return new Date(y, m - 1, d).toLocaleDateString('pt-BR');
            }
            return String(val);
        },
        isQuestionCorrect(q) {
            if (!q || q.correct === null || q.correct === undefined) return null;
            const val = this.answers[q.id];
            if (Array.isArray(q.correct)) {
                if (!Array.isArray(val)) return false;
                const a = [...q.correct].map(String).sort();
                const b = [...val].map(String).sort();
                return a.length === b.length && a.every((v, i) => v === b[i]);
            }
            return String(val ?? '') === String(q.correct);
        },
        earnedPoints(q) {
            if (!q || !this.isVisible(q)) return 0;
            const val = this.answers[q.id];
            if (['single', 'boolean', 'select'].includes(q.type)) {
                const opt = q.options.find(o => o.value === String(val));
                if (opt && opt.points !== null && opt.points !== undefined) return Number(opt.points);
            }
            if (q.type === 'multiple' && Array.isArray(val)) {
                const hasOptPoints = q.options.some(o => o.points !== null && o.points !== undefined);
                if (hasOptPoints) {
                    return val.reduce((sum, v) => {
                        const opt = q.options.find(o => o.value === String(v));
                        return sum + Number(opt?.points || 0);
                    }, 0);
                }
            }
            if (q.correct !== null && q.correct !== undefined) {
                return this.isQuestionCorrect(q) ? Number(q.points || 1) : 0;
            }
            return 0;
        },
        maxPointsFor(q) {
            if (!q || !this.isVisible(q)) return 0;
            if (['single', 'boolean', 'select'].includes(q.type)) {
                const optPoints = q.options.filter(o => o.points !== null && o.points !== undefined).map(o => Number(o.points));
                if (optPoints.length) return Math.max(0, ...optPoints);
            }
            if (q.type === 'multiple') {
                const optPoints = q.options.filter(o => o.points !== null && o.points !== undefined && Number(o.points) > 0);
                if (optPoints.length) return optPoints.reduce((s, o) => s + Number(o.points), 0);
            }
            if (q.correct !== null && q.correct !== undefined) {
                return Number(q.points || 1);
            }
            return Number(q.points || 0);
        },
        scoreSummary() {
            const active = this.activeQuestions();
            let earned = 0, max = 0, correctCount = 0, gradableCount = 0;
            for (const q of active) {
                earned += this.earnedPoints(q);
                max += this.maxPointsFor(q);
                if (q.correct !== null && q.correct !== undefined) {
                    gradableCount++;
                    if (this.isQuestionCorrect(q)) correctCount++;
                }
            }
            const percent = max > 0 ? Math.round((earned / max) * 100) : 0;
            return { earned, max, percent, correctCount, gradableCount };
        },
        submit() {
            if (this.locked) return;
            if (!this.validateAll()) return;
            this.reviewing = false;
            this.submitted = true;
            const summary = this.scoreSummary();
            this.notice = @js($completedTitle);
            this.$dispatch('questionnaire-submit', {
                answers: JSON.parse(JSON.stringify(this.answers)),
                score: this.scored ? summary : null,
            });
        },
        restart() {
            if (this.locked) return;
            this.answers = JSON.parse(JSON.stringify(this.initialDefaults));
            this.errors = {};
            this.currentIndex = 0;
            this.reviewing = false;
            this.submitted = false;
            this.notice = 'Questionário reiniciado.';
            this.$dispatch('questionnaire-reset');
        },
    }"
    x-modelable="answers"
    {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}>

    {{-- Hidden inputs for native form submission --}}
    @if ($name)
        <template x-for="q in activeQuestions()" x-bind:key="'hidden-' + q.id">
            <span>
                <template x-if="q.type === 'multiple'">
                    <span>
                        <template x-for="item in (answers[q.id] || [])" x-bind:key="item">
                            <input type="hidden" x-bind:name="@js($name) + '[' + q.id + '][]'" x-bind:value="item">
                        </template>
                    </span>
                </template>
                <template x-if="q.type !== 'multiple'">
                    <input type="hidden" x-bind:name="@js($name) + '[' + q.id + ']'" x-bind:value="answers[q.id] ?? ''">
                </template>
            </span>
        </template>
    @endif

    <p class="sr-only" role="status" aria-live="polite" x-text="notice"></p>

    @if (count($normalizedQuestions) === 0)
        <p class="rounded-nui border border-dashed border-base-300 p-8 text-center text-sm text-base-content/60">
            Nenhuma pergunta configurada.
        </p>
    @else
        {{-- Header & Progress --}}
        @if ($hasHeader || $progress)
            <header class="mb-8 flex flex-col gap-5">
                @if ($hasHeader)
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            @if ($title)
                                <h3 id="{{ $id }}-title" class="text-lg font-semibold leading-snug text-base-content">{{ $title }}</h3>
                            @endif
                            @if ($description)
                                <p class="mt-1 text-sm text-base-content/65">{{ $description }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @isset($header)
                                {{ $header }}
                            @else
                                <x-nui::tag size="sm">
                                    <span class="tabular-nums" x-text="answeredCount() + ' / ' + activeQuestions().length + ' respondidas'">
                                        0 / {{ count($normalizedQuestions) }} respondidas
                                    </span>
                                </x-nui::tag>
                            @endisset
                        </div>
                    </div>
                @endif

                @if ($progress)
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between gap-2 text-xs text-base-content/65">
                            <span class="font-medium"
                                x-text="submitted ? 'Concluído' : (reviewing ? @js($reviewLabel) : (mode === 'stepped' ? 'Pergunta ' + (currentIndex + 1) + ' de ' + activeQuestions().length : answeredCount() + ' de ' + activeQuestions().length + ' respondidas'))">
                                {{ $mode === 'stepped' ? 'Pergunta 1 de '.count($normalizedQuestions) : '0 de '.count($normalizedQuestions).' respondidas' }}
                            </span>
                            <span class="tabular-nums font-semibold text-base-content" x-text="progressPercent() + '%'">
                                {{ $mode === 'stepped' ? round(100 / max(1, count($normalizedQuestions))) : 0 }}%
                            </span>
                        </div>
                        <x-nui::progress bind="progressPercent()" size="sm" :label="$label" />
                    </div>
                @endif
            </header>
        @endif

        {{-- Questions Body --}}
        <div x-show="!reviewing && !submitted" class="flex min-w-0 flex-col gap-6">
            @foreach ($normalizedQuestions as $qIndex => $q)
                @php
                    $qId = $q['id'];
                    $fieldId = $id.'-q-'.Str::slug($qId);
                @endphp
                <div data-question="{{ $qId }}" data-question-type="{{ $q['type'] }}"
                    x-show="isVisible(@js($q)) && (mode === 'stacked' || currentQuestion()?.id === @js($qId))"
                    @if ($mode === 'stepped' && $qIndex > 0) x-cloak @endif
                    class="nui-questionnaire-item flex min-w-0 flex-col gap-5">

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-nui::tag size="sm" variant="primary">
                                <span class="tabular-nums" x-text="'Pergunta ' + (activeIndexOf(@js($qId)) + 1)">Pergunta {{ $qIndex + 1 }}</span>
                            </x-nui::tag>
                            @if ($q['section'])
                                <x-nui::tag size="sm" outline>{{ $q['section'] }}</x-nui::tag>
                            @endif
                            @if ($q['required'])
                                <span class="text-xs font-medium text-base-content/55">Obrigatória</span>
                            @else
                                <span class="text-xs text-base-content/45">Opcional</span>
                            @endif
                        </div>
                        @if ($scored && $q['points'] > 0)
                            <span class="text-xs tabular-nums text-base-content/55">
                                {{ $q['points'] }} {{ $q['points'] == 1 ? 'ponto' : 'pontos' }}
                            </span>
                        @endif
                    </div>

                    @if (in_array($q['type'], ['single', 'multiple', 'boolean', 'scale'], true))
                        <x-nui::fieldset :id="$fieldId" :legend="$q['title']" :description="$q['description']" :disabled="$locked">
                            @if (in_array($q['type'], ['single', 'boolean'], true))
                                <div class="flex flex-col gap-2.5">
                                    @foreach ($q['options'] as $optIndex => $opt)
                                        <div class="nui-questionnaire-option"
                                            x-bind:data-checked="(String(answers['{{ $qId }}']) === '{{ $opt['value'] }}').toString()"
                                            x-bind:class="{ 'is-checked': String(answers['{{ $qId }}']) === '{{ $opt['value'] }}' }"
                                            x-on:click="if (!$event.target.closest('label') && !$event.target.closest('input')) { $el.querySelector('input')?.click(); }">
                                            <x-nui::radio
                                                :id="$fieldId.'-opt-'.$optIndex"
                                                :name="$fieldId.'-radio'"
                                                :value="$opt['value']"
                                                :label="$opt['label']"
                                                :description="$opt['description']"
                                                :disabled="$locked"
                                                x-model="answers['{{ $qId }}']"
                                                x-on:change="recordChange('{{ $qId }}')" />
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($q['type'] === 'multiple')
                                <div class="flex flex-col gap-2.5">
                                    @foreach ($q['options'] as $optIndex => $opt)
                                        <div class="nui-questionnaire-option"
                                            x-bind:data-checked="(Array.isArray(answers['{{ $qId }}']) && answers['{{ $qId }}'].map(String).includes('{{ $opt['value'] }}')).toString()"
                                            x-bind:class="{ 'is-checked': Array.isArray(answers['{{ $qId }}']) && answers['{{ $qId }}'].map(String).includes('{{ $opt['value'] }}') }"
                                            x-on:click="if (!$event.target.closest('label') && !$event.target.closest('input')) { $el.querySelector('input')?.click(); }">
                                            <x-nui::checkbox
                                                :id="$fieldId.'-opt-'.$optIndex"
                                                :value="$opt['value']"
                                                :label="$opt['label']"
                                                :description="$opt['description']"
                                                :disabled="$locked"
                                                x-model="answers['{{ $qId }}']"
                                                x-on:change="recordChange('{{ $qId }}')" />
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($q['type'] === 'scale')
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-wrap items-center gap-2 p-2 rounded-nui bg-base-200" role="group" aria-label="{{ $q['title'] }}">
                                        @foreach ($q['scaleValues'] as $scaleVal)
                                            <x-nui::button
                                                variant="ghost"
                                                size="md"
                                                :disabled="$locked"
                                                class="nui-questionnaire-scale-btn min-w-11 flex-1 tabular-nums font-semibold"
                                                x-bind:class="String(answers['{{ $qId }}']) === '{{ $scaleVal }}' ? 'is-selected !bg-primary !text-primary-content !border-primary !shadow-nui-control !font-bold' : '!bg-base-100 hover:!bg-base-200 !text-base-content'"
                                                x-bind:aria-pressed="(String(answers['{{ $qId }}']) === '{{ $scaleVal }}').toString()"
                                                x-bind:data-selected="(String(answers['{{ $qId }}']) === '{{ $scaleVal }}').toString()"
                                                x-on:click="setAnswer('{{ $qId }}', '{{ $scaleVal }}')">
                                                {{ $scaleVal }}
                                            </x-nui::button>
                                        @endforeach
                                    </div>
                                    @if ($q['minLabel'] || $q['maxLabel'])
                                        <div class="flex items-center justify-between gap-4 px-1 text-xs font-medium text-base-content/70">
                                            <span>{{ $q['minLabel'] }}</span>
                                            <span>{{ $q['maxLabel'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($q['hint'])
                                <p class="text-xs text-base-content/60">{{ $q['hint'] }}</p>
                            @endif
                        </x-nui::fieldset>
                    @else
                        <div class="rounded-nui border-[length:var(--nui-border-width)] border-base-300 bg-base-100 p-5 shadow-nui">
                            @if ($q['description'])
                                <p class="mb-3 text-sm text-base-content/65">{{ $q['description'] }}</p>
                            @endif

                            @if ($q['type'] === 'rating')
                                <x-nui::rating
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :readonly="$locked"
                                    :value="(int) ($initialAnswers[$qId] ?? 0)"
                                    x-model.number="answers['{{ $qId }}']"
                                    x-on:change="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'text')
                                <x-nui::input
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :placeholder="$q['placeholder']"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:input="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'textarea')
                                <x-nui::textarea
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :placeholder="$q['placeholder']"
                                    :rows="$q['rows']"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:input="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'number')
                                <x-nui::number
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :min="$q['min']"
                                    :max="$q['max']"
                                    :step="$q['step']"
                                    :placeholder="$q['placeholder']"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:input="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'range')
                                <x-nui::range
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :min="$q['min'] ?? 0"
                                    :max="$q['max'] ?? 100"
                                    :step="$q['step'] ?? 1"
                                    :prefix="$q['prefix']"
                                    :suffix="$q['suffix']"
                                    :value="$initialAnswers[$qId] ?? ($q['min'] ?? 0)"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:change="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'select')
                                <x-nui::select
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :options="$q['options']"
                                    :placeholder="$q['placeholder'] ?: 'Selecione…'"
                                    :searchable="$q['searchable']"
                                    :value="$initialAnswers[$qId] ?? null"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:change="recordChange(@js($q))" />
                            @elseif ($q['type'] === 'date')
                                <x-nui::date
                                    :id="$fieldId"
                                    :label="$q['title']"
                                    :hint="$q['hint']"
                                    :min="$q['min'] ? (string) $q['min'] : null"
                                    :max="$q['max'] ? (string) $q['max'] : null"
                                    :placeholder="$q['placeholder'] ?: 'Escolha uma data'"
                                    :value="$initialAnswers[$qId] ?? ''"
                                    :disabled="$locked"
                                    x-model="answers['{{ $qId }}']"
                                    x-on:change="recordChange(@js($q))" />
                            @endif
                        </div>
                    @endif

                    <div x-show="errors['{{ $qId }}']" x-cloak>
                        <x-nui::alert variant="danger">
                            <span x-text="errors['{{ $qId }}']"></span>
                        </x-nui::alert>
                    </div>
                </div>
            @endforeach

            {{-- Footer Controls --}}
            <div class="nui-questionnaire-controls mt-3 flex flex-wrap items-center justify-between gap-4 pt-5">
                @if ($mode === 'stepped')
                    <x-nui::button variant="ghost" x-on:click="back()" x-bind:disabled="currentIndex === 0">
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 5.5 8l4.5 4.5" /></svg>
                        {{ $backLabel }}
                    </x-nui::button>

                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <x-nui::button variant="ghost"
                            x-show="currentQuestion() && !currentQuestion().required && !locked"
                            x-cloak
                            x-on:click="skip()">
                            {{ $skipLabel }}
                        </x-nui::button>

                        <x-nui::button x-on:click="next()" :disabled="$locked">
                            <span x-text="currentIndex >= activeQuestions().length - 1 ? (reviewEnabled ? @js($reviewLabel) : @js($submitLabel)) : @js($nextLabel)">
                                {{ count($normalizedQuestions) <= 1 ? ($review ? $reviewLabel : $submitLabel) : $nextLabel }}
                            </span>
                            <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                x-show="currentIndex < activeQuestions().length - 1 || reviewEnabled"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                        </x-nui::button>
                    </div>
                @else
                    <span class="text-xs text-base-content/60" x-text="answeredCount() + ' de ' + activeQuestions().length + ' perguntas respondidas'">
                        0 de {{ count($normalizedQuestions) }} perguntas respondidas
                    </span>
                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        @if ($review)
                            <x-nui::button variant="secondary" x-on:click="if (validateAll()) reviewing = true" :disabled="$locked">
                                {{ $reviewLabel }}
                            </x-nui::button>
                        @endif
                        <x-nui::button x-on:click="submit()" :disabled="$locked">
                            {{ $submitLabel }}
                        </x-nui::button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Review Screen --}}
        <div x-show="reviewing && !submitted" x-cloak class="nui-questionnaire-review flex min-w-0 flex-col gap-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-base font-semibold text-base-content">{{ $reviewLabel }}</h4>
                    <p class="mt-0.5 text-xs text-base-content/65">Confira suas respostas antes de concluir o envio.</p>
                </div>
                <x-nui::tag variant="primary" size="sm">
                    <span class="tabular-nums" x-text="answeredCount() + ' / ' + activeQuestions().length"></span>
                </x-nui::tag>
            </div>

            <div class="flex flex-col gap-3">
                <template x-for="(q, idx) in activeQuestions()" x-bind:key="'rev-' + q.id">
                    <x-nui::card padding="sm" variant="subtle">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold text-base-content/55 tabular-nums" x-text="'#' + (idx + 1)"></span>
                                    <p class="text-sm font-semibold text-base-content" x-text="q.title"></p>
                                </div>
                                <p class="mt-1.5 text-sm"
                                    x-bind:class="isAnswered(q) ? 'font-medium text-base-content' : 'italic text-base-content/50'"
                                    x-text="displayAnswer(q)"></p>
                            </div>
                            <x-nui::button variant="ghost" size="sm" x-on:click="jumpTo(q.id)" :disabled="$locked">
                                Alterar
                            </x-nui::button>
                        </div>
                    </x-nui::card>
                </template>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-4 pt-5">
                <x-nui::button variant="ghost" x-on:click="back()">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 5.5 8l4.5 4.5" /></svg>
                    {{ $backLabel }}
                </x-nui::button>
                <x-nui::button x-on:click="submit()" :disabled="$locked">
                    {{ $submitLabel }}
                </x-nui::button>
            </div>
        </div>

        {{-- Completed / Scored Summary Screen --}}
        <div x-show="submitted" x-cloak class="nui-questionnaire-completed flex min-w-0 flex-col gap-5">
            <x-nui::alert variant="success" :title="$completedTitle">
                {{ $completedDescription }}
            </x-nui::alert>

            @if ($scored)
                <x-nui::card padding="md" variant="subtle" title="Resultado da avaliação">
                    <x-slot:header>
                        <x-nui::tag variant="primary">
                            <span class="tabular-nums" x-text="scoreSummary().percent + '%'"></span>
                        </x-nui::tag>
                    </x-slot:header>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs text-base-content/60">Pontuação</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums">
                                <span x-text="scoreSummary().earned"></span>
                                <span class="text-sm font-normal text-base-content/55" x-text="'/ ' + scoreSummary().max + ' pts'"></span>
                            </p>
                        </div>
                        <div x-show="scoreSummary().gradableCount > 0">
                            <p class="text-xs text-base-content/60">Acertos</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums">
                                <span x-text="scoreSummary().correctCount"></span>
                                <span class="text-sm font-normal text-base-content/55" x-text="'de ' + scoreSummary().gradableCount"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60">Perguntas respondidas</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums" x-text="answeredCount() + ' / ' + activeQuestions().length"></p>
                        </div>
                    </div>
                </x-nui::card>

                <div class="flex flex-col gap-3">
                    <template x-for="(q, idx) in activeQuestions()" x-bind:key="'score-' + q.id">
                        <x-nui::card padding="sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-base-content" x-text="(idx + 1) + '. ' + q.title"></p>
                                    <p class="mt-1 text-sm text-base-content/80">
                                        Sua resposta: <strong class="font-medium text-base-content" x-text="displayAnswer(q)"></strong>
                                    </p>
                                    <p x-show="q.explanation" class="mt-2 text-xs leading-relaxed text-base-content/65" x-text="q.explanation"></p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <template x-if="isQuestionCorrect(q) === true">
                                        <x-nui::tag size="sm" variant="success">Correta</x-nui::tag>
                                    </template>
                                    <template x-if="isQuestionCorrect(q) === false">
                                        <x-nui::tag size="sm" variant="danger">Incorreta</x-nui::tag>
                                    </template>
                                    <template x-if="isQuestionCorrect(q) === null && maxPointsFor(q) > 0">
                                        <x-nui::tag size="sm" variant="primary">
                                            <span class="tabular-nums" x-text="earnedPoints(q) + ' pts'"></span>
                                        </x-nui::tag>
                                    </template>
                                </div>
                            </div>
                        </x-nui::card>
                    </template>
                </div>
            @endif

            @isset($completed)
                <div>{{ $completed }}</div>
            @endisset

            <div class="mt-3 flex flex-wrap items-center justify-end gap-4 pt-4">
                @isset($actions)
                    {{ $actions }}
                @else
                    <x-nui::button variant="secondary" x-on:click="restart()" :disabled="$locked">
                        {{ $restartLabel }}
                    </x-nui::button>
                @endisset
            </div>
        </div>
    @endif
</div>
