<script setup lang="ts">
import type { ResultItem, RunItem } from "../types";
import { fieldEntries } from "../utils";

defineProps<{ run: RunItem | null; result: ResultItem | null }>();
</script>

<template>
  <details v-if="run" class="bottom-zone" data-zone="bottom">
    <summary>المساحة العميقة المؤقتة — Event Timeline / Snapshots</summary>
    <div class="bottom-grid">
      <section>
        <p class="rail-kicker">Event Timeline</p>
        <h3>الأحداث السببية</h3>
        <ol v-if="run.events.length" class="event-ledger">
          <li v-for="event in run.events" :key="event.sequence">
            <div class="event-heading">
              <span class="timeline-sequence technical" dir="ltr">{{
                event.sequence
              }}</span
              ><strong class="technical" dir="ltr">{{
                event.event_type
              }}</strong
              ><small class="technical" dir="ltr">{{
                event.occurred_at
              }}</small>
            </div>
            <div
              v-if="fieldEntries(event.payload).length"
              class="kv-list event-payload"
            >
              <div
                v-for="field in fieldEntries(event.payload)"
                :key="field.key"
              >
                <span class="technical" dir="ltr">{{ field.key }}</span
                ><strong>{{ field.value }}</strong>
              </div>
            </div>
          </li>
        </ol>
        <p v-else class="truthful-unavailable">
          لا توجد أحداث مسجلة لهذا التشغيل.
        </p>
      </section>
      <section>
        <p class="rail-kicker">Snapshots</p>
        <h3>لقطات Runtime</h3>
        <div v-if="run.snapshots.length" class="snapshot-list">
          <article v-for="snapshot in run.snapshots" :key="snapshot.id">
            <span>Snapshot {{ snapshot.sequence }}</span
            ><small>Event {{ snapshot.event_sequence }}</small
            ><code class="technical wrap" dir="ltr">{{
              snapshot.state_digest
            }}</code
            ><time class="technical" dir="ltr">{{ snapshot.captured_at }}</time>
          </article>
        </div>
        <p v-else class="truthful-unavailable">
          لا توجد Runtime Snapshots مسجلة.
        </p>
      </section>
    </div>
  </details>

  <details v-else-if="result" class="bottom-zone" data-zone="bottom">
    <summary>المساحة العميقة المؤقتة — Frozen Payload</summary>
    <div class="bottom-grid single">
      <section>
        <p class="rail-kicker">Frozen Result Payload</p>
        <h3>الحمولة المختومة</h3>
        <div
          v-if="fieldEntries(result.sealed_payload).length"
          class="field-grid"
        >
          <div
            v-for="field in fieldEntries(result.sealed_payload)"
            :key="field.key"
            class="field-cell"
          >
            <small class="technical" dir="ltr">{{ field.key }}</small
            ><strong>{{ field.value }}</strong>
          </div>
        </div>
        <p v-else class="truthful-unavailable">
          لا توجد حقول Frozen Payload منظّمة للعرض.
        </p>
      </section>
    </div>
  </details>
</template>
