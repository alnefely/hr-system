# x-dataTable.table — دليل وأمثلة الاستخدام الشامل 🚀

هذا المكون يسهل لك إنشاء جداول DataTables مع دعم كامل للـ Server-side، الأزرار، التصدير، والبحث المتطور (شامل قوائم الاختيار وإلغاء البحث عن أعمدة معينة).

---

## 1️⃣ أبسط مثال — جدول مباشر بدون أزرار
```blade
<x-dataTable.table
    :columns="['#', 'الاسم', 'التاريخ']"
    :fields="['id', 'name', 'created_at']"
/>
```

---

## 2️⃣ مثال كامل للإجراءات (عرض، تعديل، حذف) مع زر الإضافة
```blade
<x-dataTable.table
    :columns="[
        __('trans.id'),
        __('trans.admin.name'),
        __('trans.email'),
        __('trans.created_at'),
    ]"
    :fields="['id', 'name', 'email', 'created_at']"
    :actions="[
        ['type' => 'view',   'url' => url('admin/admins'), 'label' => 'عرض التفاصيل'],
        ['type' => 'edit',   'url' => url('admin/admins')],
        ['type' => 'delete', 'url' => url('admin/admins')],
    ]"
    add-new="إضافة مشرف جديد"
    add-new-url="{{ route('admin.admins.create') }}"
/>
```

---

## 3️⃣ جدول بـ Ajax URL مخصص للبيانات + Server-Side
يستخدم الجدول رابط الصفحة الحالية افتراضياً لجلب البيانات في حال كان الـ Server-Side مفعل، يمكنك تغييره كالتالي:
```blade
<x-dataTable.table
    :columns="['#', 'الطالب', 'الحالة']"
    :fields="['id', 'student.name', 'status']"
    url="/admin/api/students/data"
    :server-side="true"
    :order="[[0, 'ASC']]"
/>
```

---

## 4️⃣ إيقاف عرض أزرار التصدير وإخفاء البحث الكلي (Footer)
```blade
<x-dataTable.table
    :columns="['#', 'العملية', 'المبلغ', 'التاريخ']"
    :fields="['id', 'operation', 'amount', 'created_at']"
    :exportable="false"  {{-- 👈 لا أزرار للطباعة أو الإكسيل وغيرها --}}
    :searchable="false"  {{-- 👈 لا حقل بحث أسفل الجدول --}}
/>
```

---

## 5️⃣ إجراء Custom (تصميم زر إضافي بجانب التعديل والحذف)
```blade
@php
$actions = [
    ['type' => 'edit', 'url' => url('admin/admins')],
    ['type' => 'delete', 'url' => url('admin/admins')],
    [
        'type' => 'custom',
        'html' => '<li>
            <a href="/admin/reports/${row.id}" data-id="${row.id}" class="dropdown-item">
                <i class="fa-solid fa-chart-bar fa-fw"></i> التقرير
            </a>
        </li>'
    ],
];
@endphp

<x-dataTable.table
    :columns="['#', 'الاسم']"
    :fields="['id', 'name']"
    :actions="$actions"
/>
```

---

## 6️⃣ منع البحث عن حقل معين أو منعه من الترتيب
بدلاً من تمرير الحقل كنص عادي (`'email'`)، نمرره كمصفوفة (Array) ونخبره ألا يقبل البحث أو الترتيب:
```blade
<x-dataTable.table
    :columns="['#', 'الاسم', 'الصورة', 'البريد']"
    :fields="[
        'id',
        'name',
        ['data' => 'img', 'orderable' => false, 'searchable' => false], // 👈 لا بحث، لا ترتيب
        ['data' => 'email', 'searchable' => false],                     // 👈 لا بحث، ترتيب مسموح
    ]"
/>
```

---

## 7️⃣ عرض حقل البحث السفلي كقائمة منسدلة (Select) بدلاً من Input نصي
مفيدة جداً للبحث في الأعمدة مثل الصلاحيات (Roles) أو الحالات (نشط/معطل).
```blade
<x-dataTable.table
    :columns="['#', 'الاسم', 'الدور']"
    :fields="[
        'id',
        'name',
        // هنا قمنا بتحويل مربع البحث إلى قائمة منسدلة بخيارات:
        [
            'data' => 'role.name', 
            'searchType' => 'select', 
            'searchOptions' => [
                'مدير عام' => 'مدير عام',
                'محرر' => 'محرر'
            ]
        ],
    ]"
/>
```

---

## 8️⃣ التحكم الكامل بالشكل عبر `$extra` (رسم صور، شارات Badges، أزرار مخصصة)
هذا يهم جداً عندما تكون الحقول القادمة من الداتا بيس بحاجة לתنسيق برمجي كالمسار الكامل للصور، أو ترجمة حالة لـ Badge ملون. (تأكد دائماً أن دوال الجافاسكريبت تستقبل 4 متغيرات: data, type, row, meta).

```blade
@php
  $extra = [
      // تعديل العمود رقم 2 الخاص بصورة المستخدم (العد يبدأ من 0)
      [
          'targets' => 2,
          'render'  => 'function(data, type, row, meta){ 
              return data ? `<img loading="lazy" src="${data}" alt="${row.name}" width="50" height="50" class="rounded avatar-sm" />` : "";
          }',
      ],
      // تعديل العمود رقم 3 الخاص بالحالة
      [
          'targets' => 3,
          'render'  => 'function(data, type, row, meta){ 
              return data == 1
                  ? \'<span class="badge bg-success">نشط</span>\'
                  : \'<span class="badge bg-danger">معطل</span>\'; 
          }'
      ]
    ];
@endphp

<x-dataTable.table
    id="custom-table"
    class="text-center bg-white"
    :columns="['#', 'الاسم', 'الصورة', 'الحالة']"
    :fields="['id', 'name', 'img', 'is_active']"
    :extra="$extra"
/>
```
