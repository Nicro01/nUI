@props([
    'events' => [], 'date' => null, 'value' => '', 'name' => null,
    'startHour' => 8, 'endHour' => 18, 'step' => 30,
    'locale' => 'pt-BR', 'label' => 'Agenda semanal', 'readonly' => false,
])

@php
    $startHour = (int) $startHour;
    $endHour = (int) $endHour;
    $step = (int) $step;
    if ($startHour < 0 || $endHour > 24 || $endHour <= $startHour || !in_array($step, [15, 30, 60], true)) {
        throw new InvalidArgumentException('Calendar Grid: use horas entre 0 e 24, fim após início e step de 15, 30 ou 60 minutos.');
    }
    $validDate = static function (string $value, string $format): bool {
        $parsed = DateTimeImmutable::createFromFormat('!'.$format, $value);
        return $parsed && $parsed->format($format) === $value;
    };
    $date ??= now()->format('Y-m-d');
    if (!$validDate((string) $date, 'Y-m-d')) {
        throw new InvalidArgumentException('Calendar Grid: date deve usar YYYY-MM-DD.');
    }
    $normalized = [];
    $ids = [];
    foreach ($events as $event) {
        $eventId = (string) ($event['id'] ?? '');
        $start = (string) ($event['start'] ?? '');
        $end = (string) ($event['end'] ?? '');
        if ($eventId === '' || isset($ids[$eventId]) || !$validDate($start, 'Y-m-d\TH:i') || !$validDate($end, 'Y-m-d\TH:i') || $end <= $start) {
            throw new InvalidArgumentException('Calendar Grid: eventos precisam de id único e start/end locais em YYYY-MM-DDTHH:mm, com fim após início.');
        }
        $ids[$eventId] = true;
        $normalized[] = ['id' => $eventId, 'title' => (string) ($event['title'] ?? ''), 'start' => $start, 'end' => $end, 'variant' => (string) ($event['variant'] ?? 'primary')];
    }
    $locked = $readonly || (bool) $attributes->get('disabled');
    $id = $attributes->get('id') ?? 'nui-calendar-'.Str::random(8);
@endphp

<div data-calendar-events="{{ json_encode($normalized) }}"
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except('disabled')->class('nui-calendar min-w-0 w-full text-base-content') }}
    x-modelable="value" {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
    x-data="{
        value: @js((string) $value), anchor: @js((string) $date), events: [],
        startHour: @js($startHour), endHour: @js($endHour), step: @js($step),
        locale: @js($locale), locked: @js($locked), focusDay: 0, focusRow: 0,
        observer: null, cursor: '', pickYear: '', pickMonth: '', pickDay: '',
        pan: null, panning: false, suppressClick: false, panReset: null,
        init() {
            this.events = JSON.parse(this.$el.dataset.calendarEvents);
            this.cursor = this.anchor;
            this.anchor = this.monday(this.cursor);
            this.syncPicker();
            ['pickYear','pickMonth','pickDay'].forEach(key => this.$watch(key, () => this.pickDate()));
            this.observer = new MutationObserver(() => { this.events = JSON.parse(this.$el.dataset.calendarEvents); });
            this.observer.observe(this.$el, { attributes: true, attributeFilter: ['data-calendar-events'] });
            this.$watch('value', value => {
                if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) {
                    this.cursor = value.slice(0,10); this.anchor = this.monday(this.cursor); this.syncPicker();
                }
            });
        },
        destroy() { this.observer?.disconnect(); clearTimeout(this.panReset); },
        parse(value) { const [date, time = '00:00'] = value.split('T'); const [y,m,d] = date.split('-').map(Number); const [h,min] = time.split(':').map(Number); return new Date(y,m-1,d,h,min); },
        key(date) { return date.getFullYear() + '-' + String(date.getMonth()+1).padStart(2,'0') + '-' + String(date.getDate()).padStart(2,'0'); },
        add(value, count) { const date = this.parse(value); date.setDate(date.getDate()+count); return this.key(date); },
        monday(value) { const date = this.parse(value); date.setDate(date.getDate() - (date.getDay()+6)%7); return this.key(date); },
        get days() { return Array.from({length:7}, (_,index) => this.add(this.anchor,index)); },
        get rows() { return Array.from({length:(this.endHour-this.startHour)*60/this.step}, (_,index) => index); },
        get period() { const format = new Intl.DateTimeFormat(this.locale,{day:'numeric',month:'short',year:'numeric'}); return format.format(this.parse(this.anchor)) + ' – ' + format.format(this.parse(this.add(this.anchor,6))); },
        dayLabel(day) { return new Intl.DateTimeFormat(this.locale,{weekday:'short',day:'numeric',month:'short'}).format(this.parse(day)); },
        dayName(day) { return new Intl.DateTimeFormat(this.locale,{weekday:'short'}).format(this.parse(day)).replace('.',''); },
        dayNumber(day) { return String(this.parse(day).getDate()).padStart(2,'0'); },
        isToday(day) { return day === this.key(new Date()); },
        get pickerYears() { const years=Array.from({length:201},(_,i)=>1900+i); const year=this.parse(this.cursor || this.anchor).getFullYear(); if (!years.includes(year)) years.push(year); return years.sort((a,b)=>a-b).map(year=>({value:String(year),label:String(year)})); },
        get pickerMonths() { return Array.from({length:12},(_,month)=>({value:String(month+1),label:new Intl.DateTimeFormat(this.locale,{month:'long'}).format(new Date(2024,month,1))})); },
        get pickerDays() { const count=new Date(Number(this.pickYear),Number(this.pickMonth),0).getDate() || 31; return Array.from({length:count},(_,i)=>({value:String(i+1),label:String(i+1).padStart(2,'0')})); },
        syncPicker() { const date=this.parse(this.cursor); this.pickYear=String(date.getFullYear()); this.pickMonth=String(date.getMonth()+1); this.pickDay=String(date.getDate()); },
        pickDate() {
            if (!this.pickYear || !this.pickMonth || !this.pickDay) return;
            this.pickDay=String(Math.min(Number(this.pickDay),this.pickerDays.length));
            const next=this.pickYear+'-'+this.pickMonth.padStart(2,'0')+'-'+this.pickDay.padStart(2,'0');
            if (next===this.cursor) return;
            this.cursor=next; this.anchor=this.monday(next); this.emitPeriod();
        },
        emitPeriod() { this.$dispatch('calendar-period', {start:this.anchor,end:this.add(this.anchor,7)}); },
        time(minutes) { return String(Math.floor(minutes/60)).padStart(2,'0') + ':' + String(minutes%60).padStart(2,'0'); },
        stamp(day, minutes) { if (minutes === 1440) return this.add(day,1) + 'T00:00'; return day + 'T' + this.time(minutes); },
        move(count) { this.cursor=this.add(this.cursor,count*7); this.anchor=this.monday(this.cursor); this.syncPicker(); this.focusDay=0; this.focusRow=0; this.emitPeriod(); },
        today() { this.cursor=this.key(new Date()); this.anchor=this.monday(this.cursor); this.syncPicker(); this.emitPeriod(); },
        startPan(event) { if (event.button!==0 || event.pointerType==='touch') return; const view=this.$refs.viewport; this.pan={id:event.pointerId,x:event.clientX,y:event.clientY,left:view.scrollLeft,top:view.scrollTop,active:false}; },
        movePan(event) {
            if (!this.pan || this.pan.id!==event.pointerId) return;
            const dx=event.clientX-this.pan.x,dy=event.clientY-this.pan.y;
            if (!this.pan.active && Math.hypot(dx,dy)<6) return;
            const view=this.$refs.viewport;
            if (!this.pan.active) { this.pan.active=true; this.panning=true; view.setPointerCapture(event.pointerId); }
            event.preventDefault(); view.scrollLeft=this.pan.left-dx; view.scrollTop=this.pan.top-dy;
        },
        endPan(event) {
            if (!this.pan || this.pan.id!==event.pointerId) return;
            const active=this.pan.active; this.pan=null; this.panning=false;
            if (active) { this.suppressClick=true; clearTimeout(this.panReset); this.panReset=setTimeout(()=>this.suppressClick=false,0); }
            const view=this.$refs.viewport;
            if (view.hasPointerCapture(event.pointerId)) view.releasePointerCapture(event.pointerId);
        },
        select(day, row) {
            if (this.locked) return;
            const minutes = this.startHour*60 + row*this.step;
            this.value = this.stamp(day,minutes);
            this.focusDay = this.days.indexOf(day); this.focusRow = row;
            this.$dispatch('calendar-select', {start:this.value,end:this.stamp(day,minutes+this.step),date:day});
        },
        open(event) { if (!this.locked) { this.value = event.start; this.$dispatch('calendar-event', { ...event }); } },
        navigate(event, day, row) {
            const directions = {ArrowLeft:[-1,0],ArrowRight:[1,0],ArrowUp:[0,-1],ArrowDown:[0,1],Home:[0,-row],End:[0,this.rows.length-1-row]};
            if (!directions[event.key]) return;
            event.preventDefault();
            this.focusDay = Math.max(0,Math.min(6,day+directions[event.key][0]));
            this.focusRow = Math.max(0,Math.min(this.rows.length-1,row+directions[event.key][1]));
            this.$nextTick(() => this.$root.querySelector('[data-slot=\''+this.focusDay+'-'+this.focusRow+'\']')?.focus());
        },
        dayEvents(day) {
            const start = this.parse(this.stamp(day,this.startHour*60));
            const end = this.parse(this.stamp(day,this.endHour*60));
            const items = this.events.filter(event => this.parse(event.end)>start && this.parse(event.start)<end).map(event => ({...event,top:Math.max(0,(this.parse(event.start)-start)/60000),bottom:Math.min((this.endHour-this.startHour)*60,(this.parse(event.end)-start)/60000)})).sort((a,b)=>a.top-b.top || b.bottom-a.bottom);
            let group=[],lanes=[],until=-1;
            const finish = () => group.forEach(event => event.columns=lanes.length);
            items.forEach(event => {
                if (event.top>=until) { finish(); group=[]; lanes=[]; }
                let lane=lanes.findIndex(end=>end<=event.top);
                if (lane<0) lane=lanes.length;
                lanes[lane]=event.bottom; event.lane=lane; group.push(event); until=Math.max(...lanes);
            });
            finish(); return items;
        },
        position(event) { return {top:(event.top/this.step*40)+'px',height:Math.max(24,(event.bottom-event.top)/this.step*40-3)+'px',left:'calc('+(event.lane/event.columns*100)+'% + 3px)',width:'calc('+(100/event.columns)+'% - 6px)'}; },
        tone(event) { return ({primary:'!bg-primary !border-primary !text-primary-content',success:'!bg-success !border-success !text-success-content',warning:'!bg-warning !border-warning !text-warning-content',danger:'!bg-danger !border-danger !text-danger-content',neutral:'!bg-base-200 !border-base-300 !text-base-content'})[event.variant] || '!bg-primary !border-primary !text-primary-content'; }
    }">
    @if ($name)<x-nui::input type="hidden" :name="$name" x-bind:value="value" />@endif
    <div class="nui-calendar-toolbar flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h3 id="{{ $id }}-label" class="nui-calendar-title">{{ $label }}</h3>
            <p class="mt-1 text-sm text-base-content/65" aria-live="polite" x-text="period"></p>
        </div>
        <div class="flex items-center gap-2" role="group" aria-label="Navegar na agenda">
            <x-nui::button variant="ghost" size="sm" x-on:click="move(-1)" aria-label="Semana anterior"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 5-5 5 5 5" /></svg></x-nui::button>
            <x-nui::button variant="secondary" size="sm" x-on:click="today()">Hoje</x-nui::button>
            <x-nui::button variant="ghost" size="sm" x-on:click="move(1)" aria-label="Próxima semana"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m8 5 5 5-5 5" /></svg></x-nui::button>
        </div>
    </div>
    <div class="nui-calendar-date-filter grid grid-cols-2 gap-3 sm:grid-cols-[1fr_1.4fr_.8fr]" role="group" aria-label="Escolher data da agenda">
        <x-nui::select label="Ano" x-model="pickYear" options-bind="pickerYears" searchable />
        <x-nui::select label="Mês" x-model="pickMonth" options-bind="pickerMonths" searchable />
        <x-nui::select label="Dia" x-model="pickDay" options-bind="pickerDays" />
    </div>
    <p id="{{ $id }}-help" class="sr-only">Arraste a grade com o mouse ou role para navegar. Use as setas para percorrer dias e horários, Home e End para início e fim do dia. Pressione Enter para selecionar. Horas e datas permanecem fixas durante a rolagem.</p>
    <div x-ref="viewport" class="nui-calendar-scroll max-h-[36rem] overflow-auto" role="region" aria-labelledby="{{ $id }}-label" aria-describedby="{{ $id }}-help" tabindex="0"
        x-bind:data-panning="panning" x-on:pointerdown="startPan($event)" x-on:pointermove="movePan($event)" x-on:pointerup="endPan($event)" x-on:pointercancel="endPan($event)" x-on:lostpointercapture="endPan($event)"
        x-on:click.capture="if (suppressClick) { $event.preventDefault(); $event.stopImmediatePropagation(); }" x-on:dragstart.prevent>
        <div class="grid min-w-[860px] grid-cols-[56px_repeat(7,minmax(0,1fr))]">
            <div class="nui-calendar-corner sticky left-0 top-0 z-30 flex items-end justify-center pb-4 text-[10px] font-semibold uppercase tracking-wide text-base-content/60">Hora</div>
            <template x-for="day in days" :key="day">
                <div class="nui-calendar-day sticky top-0 z-20 border-l border-base-300 px-2 py-3 text-center" x-bind:data-today="isToday(day)" x-bind:data-cursor="day===cursor">
                    <span class="block text-[10px] font-semibold uppercase tracking-[.06em] text-base-content/65" x-text="dayName(day)"></span>
                    <span class="nui-calendar-day-number" x-text="dayNumber(day)"></span>
                    <span class="sr-only" x-show="isToday(day)">Hoje</span>
                </div>
            </template>
            <div class="nui-calendar-hours sticky left-0 z-20" aria-hidden="true">
                <template x-for="row in rows" :key="row"><div class="flex h-10 justify-center border-b border-base-content/10 pt-2 text-[10px] tabular-nums text-base-content/65" x-text="time(startHour*60+row*step)"></div></template>
            </div>
            <template x-for="(day, dayIndex) in days" :key="day">
                <div class="relative border-l border-base-content/10">
                    <template x-for="row in rows" :key="row">
                        <x-nui::button variant="ghost" x-bind:disabled="locked" class="nui-calendar-slot group !flex !h-10 !w-full !rounded-none !border-x-0 !border-t-0 !border-b !border-base-content/10 !p-0 !shadow-none disabled:!opacity-100 focus-visible:!outline-offset-[-2px]"
                            x-bind:data-slot="dayIndex+'-'+row" x-bind:tabindex="dayIndex===focusDay && row===focusRow ? 0 : -1"
                            x-bind:aria-label="dayLabel(day) + ', ' + time(startHour*60+row*step)" x-bind:aria-pressed="value===stamp(day,startHour*60+row*step)"
                            x-bind:data-selected="value===stamp(day,startHour*60+row*step)" x-bind:data-hour-end="(startHour*60+(row+1)*step)%60===0"
                            x-on:keydown="navigate($event,dayIndex,row)" x-on:click="select(day,row)">
                            <span class="sr-only" x-text="time(startHour*60+row*step)"></span>
                            <svg aria-hidden="true" class="size-3 opacity-0 group-hover:opacity-50 group-focus-visible:opacity-75" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 5v10M5 10h10" /></svg>
                        </x-nui::button>
                    </template>
                    <template x-for="event in dayEvents(day)" :key="event.id">
                        <x-nui::button variant="ghost" x-bind:disabled="locked" class="nui-calendar-event !absolute !z-10 !flex !min-w-0 !flex-col !items-start !justify-start !gap-1 !overflow-hidden !whitespace-normal !rounded-nui-field !border !px-1.5 !py-1.5 !text-left !text-[11px] !shadow-nui-control disabled:!opacity-100"
                            x-bind:style="position(event)" x-bind:class="tone(event)" x-bind:aria-label="event.title+', '+event.start.replace('T',' ')+ ' até '+event.end.replace('T',' ')" x-on:click="open(event)">
                            <span class="block w-full text-[10px] tabular-nums opacity-80" x-text="event.start.slice(11)"></span>
                            <span class="line-clamp-3 w-full break-words font-semibold leading-snug" x-text="event.title"></span>
                        </x-nui::button>
                    </template>
                </div>
            </template>
        </div>
    </div>
    <p class="mt-3 text-xs text-base-content/60">{{ $locked ? 'Agenda em modo de leitura. Arraste a grade para navegar.' : 'Arraste para navegar. Selecione um horário para agendar ou um compromisso para editar.' }}</p>
</div>
