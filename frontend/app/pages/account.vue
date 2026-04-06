<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Account',
  description: 'Account page'
})

const auth = useAuthStore()

const pending = ref(false)
const pageError = ref('')
const passwordError = ref('')
const passwordPending = ref(false)
const passwordSuccess = ref('')

const form = reactive({
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

function readError(error: unknown, fallback: string) {
  if (!error || typeof error !== 'object') {
    return fallback
  }

  const fetchError = error as {
    data?: { message?: string, errors?: Record<string, string[]> }
    message?: string
    statusMessage?: string
  }

  const errors = fetchError.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first[0]) {
      return first[0]
    }
  }

  return fetchError.data?.message || fetchError.statusMessage || fetchError.message || fallback
}

async function fetchAccount() {
  pending.value = true
  pageError.value = ''

  try {
    await auth.fetchMe()
  } catch (error) {
    pageError.value = readError(error, 'Unable to load account.')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await fetchAccount()
})

const user = computed(() => auth.currentUser)

const canSubmitPassword = computed(() => {
  return Boolean(form.password && form.password_confirmation)
})

async function submitPassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (form.password.length < 8) {
    passwordError.value = 'Password must be at least 8 characters.'
    return
  }

  if (form.password !== form.password_confirmation) {
    passwordError.value = 'Passwords do not match.'
    return
  }

  passwordPending.value = true

  try {
    await auth.request('/auth/password', {
      method: 'PUT',
      body: {
        password: form.password,
        password_confirmation: form.password_confirmation,
      },
    })

    form.password = ''
    form.password_confirmation = ''
    passwordSuccess.value = 'Password updated successfully.'
  } catch (error) {
    passwordError.value = readError(error, 'Unable to update password.')
  } finally {
    passwordPending.value = false
  }
}
</script>

<template>
  <UCard class="shadow-sm ring-1 ring-default">
    <template #header>
      <h1 class="text-2xl font-semibold text-highlighted">
        Account
      </h1>
    </template>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
      class="mb-4"
    />

    <div class="grid gap-6 md:grid-cols-[1.1fr_1fr]">
      <div>
        <div v-if="pending" class="space-y-3">
          <USkeleton class="h-6 w-1/2" />
          <USkeleton class="h-5 w-1/3" />
          <USkeleton class="h-5 w-1/4" />
        </div>

        <div v-else class="space-y-1.5">
          <p class="text-lg font-semibold text-highlighted">
            {{ user?.name || '-' }}
          </p>
          <UBadge v-if="user?.role" color="primary" variant="soft">
            {{ user.role }}
          </UBadge>
          <p class="text-sm text-toned">
            {{ user?.email || '-' }}
          </p>
        </div>
      </div>

      <div class="space-y-3">
        <div>
          <h2 class="text-sm font-semibold text-highlighted">
            Update password
          </h2>
          <p class="text-xs text-muted">
            Enter a new password and confirm it below.
          </p>
        </div>

        <UAlert
          v-if="passwordError"
          color="error"
          variant="subtle"
          title="Update failed"
          :description="passwordError"
        />

        <UAlert
          v-if="passwordSuccess"
          color="primary"
          variant="subtle"
          title="Success"
          :description="passwordSuccess"
        />

        <div class="grid gap-3">
          <UFormField label="New password" name="password">
            <UInput
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="New password"
            >
              <template #trailing>
                <UButton
                  type="button"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  @click="showPassword = !showPassword"
                />
              </template>
            </UInput>
          </UFormField>

          <UFormField label="Confirm password" name="password_confirmation">
            <UInput
              v-model="form.password_confirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Confirm password"
            >
              <template #trailing>
                <UButton
                  type="button"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  :icon="showPasswordConfirmation ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                  :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'"
                  @click="showPasswordConfirmation = !showPasswordConfirmation"
                />
              </template>
            </UInput>
          </UFormField>
        </div>

        <div class="flex justify-end">
          <UButton
            color="primary"
            variant="solid"
            :loading="passwordPending"
            :disabled="!canSubmitPassword"
            @click="submitPassword"
          >
            Update password
          </UButton>
        </div>
      </div>
    </div>
  </UCard>
</template>
