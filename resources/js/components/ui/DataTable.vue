<script setup lang="ts" generic="T">
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'

interface Column {
  key: string
  label: string
  class?: string
}

interface Props {
  columns: Column[]
  data: T[]
  keyField?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  keyField: 'id',
  loading: false
})
</script>

<template>
  <div>
    <!-- Desktop Table View -->
    <div class="hidden rounded-xl border border-border/50 bg-card text-card-foreground shadow-sm md:block">
      <Table>
        <TableHeader>
          <TableRow class="hover:bg-transparent border-b border-border/60">
            <TableHead v-for="col in columns" :key="col.key" :class="col.class" class="h-12 text-xs font-semibold uppercase tracking-wider text-muted-foreground/80">
              {{ col.label }}
            </TableHead>
            <TableHead v-if="$slots.actions" class="w-[50px]"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="i in 5" :key="i">
                    <TableCell :colspan="columns.length + ($slots.actions ? 1 : 0)" class="h-16 animate-pulse bg-muted/20" />
                </TableRow>
            </template>
            <template v-else-if="data.length === 0">
                 <TableRow>
                    <TableCell :colspan="columns.length + ($slots.actions ? 1 : 0)" class="h-24 text-center text-muted-foreground">
                        No results found.
                    </TableCell>
                </TableRow>
            </template>
            <template v-else>
              <TableRow v-for="row in data" :key="(row as any)[keyField]" class="transition-colors hover:bg-muted/30 border-b border-border/40">
                <TableCell v-for="col in columns" :key="col.key" :class="col.class" class="py-3 font-medium text-sm">
                  <slot :name="`cell-${col.key}`" :row="row" :value="(row as any)[col.key]">
                    {{ (row as any)[col.key] }}
                  </slot>
                </TableCell>
                <TableCell v-if="$slots.actions" class="py-3 text-right">
                    <slot name="actions" :row="row" />
                </TableCell>
              </TableRow>
            </template>
        </TableBody>
      </Table>
    </div>

    <!-- Mobile Card View -->
    <div class="space-y-4 md:hidden">
        <template v-if="loading">
            <div v-for="i in 3" :key="i" class="h-32 w-full animate-pulse rounded-xl bg-muted/20" />
        </template>
        <template v-else-if="data.length === 0">
            <div class="flex h-32 items-center justify-center rounded-xl border border-dashed text-muted-foreground">
                No results found.
            </div>
        </template>
        <template v-else>
            <Card v-for="row in data" :key="(row as any)[keyField]" class="overflow-hidden">
                <CardHeader v-if="$slots.mobileHeader || columns[0]" class="bg-muted/10 px-4 py-3 border-b border-border/30">
                     <slot name="mobileHeader" :row="row">
                        <CardTitle class="text-base">
                            {{ (row as any)[columns[0].key] }}
                        </CardTitle>
                     </slot>
                </CardHeader>
                <CardContent class="grid gap-3 p-4">
                    <slot name="mobileContent" :row="row">
                        <div v-for="col in columns.slice(1)" :key="col.key" class="grid grid-cols-2 gap-2 text-sm">
                             <div class="font-medium text-muted-foreground">{{ col.label }}</div>
                             <div class="text-right">
                                <slot :name="`cell-${col.key}`" :row="row" :value="(row as any)[col.key]">
                                    {{ (row as any)[col.key] }}
                                </slot>
                             </div>
                        </div>
                    </slot>
                </CardContent>
                <CardFooter v-if="$slots.actions" class="bg-muted/10 px-4 py-2 border-t border-border/30 flex justify-end gap-2">
                     <slot name="actions" :row="row" />
                </CardFooter>
            </Card>
        </template>
    </div>
  </div>
</template>
