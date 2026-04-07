<script setup lang="ts">
type EventRow = {
  id: number
  title: string
  description: string | null
  img?: string | null
  starts_at: string | null
  ends_at: string | null
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  event: EventRow | null
}>()

function toLocalInput(value: string | null) {
  if (!value) {
    return ''
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const pad = (unit: number) => unit.toString().padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const detail = computed(() => ({
  title: props.event?.title ?? '',
  description: props.event?.description ?? '',
  img: props.event?.img ?? null,
  starts_at: toLocalInput(props.event?.starts_at ?? null),
  ends_at: toLocalInput(props.event?.ends_at ?? null),
}))
</script>

<template>
  <UModal v-model:open="open" title="Event details" class="w-full max-w-3xl">
    <template #body>
      <div class="space-y-4">
        <div class="grid gap-6 md:grid-cols-2">
          <div class="space-y-4">
            <UFormField label="Title" name="event-title">
              <UInput :model-value="detail.title" class="w-full" readonly />
            </UFormField>

            <UFormField label="Description" name="event-description">
              <div class="min-h-[120px] whitespace-pre-wrap rounded-md border border-default bg-muted/10 px-3 py-2 text-sm text-toned">
                {{ detail.description || 'No description provided.' }}
              </div>
            </UFormField>

            <UFormField label="Starts at" name="event-start">
              <UInput :model-value="detail.starts_at" type="datetime-local" class="w-full" readonly />
            </UFormField>

            <UFormField label="Ends at" name="event-end">
              <UInput :model-value="detail.ends_at" type="datetime-local" class="w-full" readonly />
            </UFormField>
          </div>

          <div class="space-y-3">
            <UFormField v-if="detail.img" label="Event image" name="event-image">
              <div class="overflow-hidden rounded-lg border border-default bg-muted/10">
                <img :src="detail.img" alt="Event image" class="h-60 w-full object-cover" />
              </div>
            </UFormField>

            <div v-else class="rounded-lg border border-dashed border-default/70 bg-muted/10 p-6 text-center text-sm text-muted">
              No image uploaded.
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Close
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>
