import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/community_provider.dart';
import '../widgets/post_card.dart';

class PostsScreen extends StatefulWidget {
  final int? courseId;

  const PostsScreen({
    Key? key,
    this.courseId,
  }) : super(key: key);

  @override
  State<PostsScreen> createState() => _PostsScreenState();
}

class _PostsScreenState extends State<PostsScreen> {
  @override
  void initState() {
    super.initState();
    _loadPosts();
  }

  Future<void> _loadPosts() async {
    final provider = context.read<CommunityProvider>();
    await provider.loadPosts(courseId: widget.courseId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Comunidade'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outlined),
            onPressed: () {
              Navigator.of(context).pushNamed('/post-form').then((result) {
                if (result == true) {
                  _loadPosts();
                }
              });
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadPosts,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                padding: const EdgeInsets.all(24),
                color: const Color(0xFF0066FF),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Comunidade',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        fontFamily: 'Poppins',
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Compartilhe dúvidas e experiências',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white70,
                        fontFamily: 'Inter',
                      ),
                    ),
                  ],
                ),
              ),

              // Posts List
              Padding(
                padding: const EdgeInsets.all(16),
                child: Consumer<CommunityProvider>(
                  builder: (context, provider, _) {
                    if (provider.isLoading) {
                      return ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: 3,
                        itemBuilder: (context, index) {
                          return const Padding(
                            padding: EdgeInsets.only(bottom: 16),
                            child: PostShimmer(),
                          );
                        },
                      );
                    }

                    if (provider.posts.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Column(
                            children: [
                              Icon(
                                Icons.people_outline,
                                size: 64,
                                color: Colors.grey[300],
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Nenhum post ainda',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey[600],
                                  fontFamily: 'Inter',
                                ),
                              ),
                              const SizedBox(height: 24),
                              ElevatedButton.icon(
                                onPressed: () {
                                  Navigator.of(context)
                                      .pushNamed('/post-form')
                                      .then((result) {
                                    if (result == true) {
                                      _loadPosts();
                                    }
                                  });
                                },
                                icon: const Icon(Icons.add),
                                label: const Text('Criar novo post'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF0066FF),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: provider.posts.length,
                      itemBuilder: (context, index) {
                        final post = provider.posts[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: PostCard(
                            post: post,
                            onTap: () {
                              Navigator.of(context)
                                  .pushNamed(
                                    '/post-detail',
                                    arguments: post.id,
                                  )
                                  .then((result) {
                                if (result == true) {
                                  _loadPosts();
                                }
                              });
                            },
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Shimmer Loading Widget
class PostShimmer extends StatelessWidget {
  const PostShimmer({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 180,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}
